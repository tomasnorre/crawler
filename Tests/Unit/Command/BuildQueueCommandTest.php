<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Command;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AOE\Crawler\Command\BuildQueueCommand;
use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\InvokeQueueChangeEvent;
use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\Handler\CrawlUrlHandler;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\PageService;
use AOE\Crawler\Service\PageTreeUrlCollector;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use AOE\Crawler\Tests\Unit\FrozenClock;
use AOE\Crawler\Value\QueueRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Runs the command with the real PageTreeUrlCollector and CrawlUrlHandler, whose dependencies are test doubles.
 * The page tree comes from a PageTreeView test double; without TCA, BackendUtility neither reads the root page
 * nor page titles from the database.
 *
 * The output is decorated, so the tests also see the styles of the output and the redrawing of the progress bar.
 */
#[CoversClass(BuildQueueCommand::class)]
final class BuildQueueCommandTest extends UnitTestCase
{
    private const ROOT_PAGE_ID = 1;

    private const NOW = '2026-11-27 10:00:00';

    /**
     * Moves the cursor to the start of the line and clears it, written when the progress bar is redrawn or cleared
     */
    private const CLEAR_LINE = "\e[1G\e[2K";

    protected bool $resetSingletonInstances = true;

    private EventDispatcher&MockObject $eventDispatcher;

    /**
     * The queue repository of the command and the CrawlUrlHandler
     */
    private QueueRepository&MockObject $queueRepository;

    /**
     * The queue repository of the QueueEntryWriter
     */
    private QueueRepository&MockObject $writerQueueRepository;

    private PageRepository&MockObject $pageRepository;

    private QueueExecutor&MockObject $queueExecutor;

    private PageTreeView&MockObject $pageTree;

    /**
     * The pages getTree() of the page tree returns
     */
    private array $treePages = [];

    /**
     * The configurations of each page, by page id
     *
     * @var array<int, array>
     */
    private array $pageConfigurations = [];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'Y-m-d';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] = 'H:i';

        $backendUser = $this->createStub(BackendUserAuthentication::class);
        $backendUser->method('getPagePermsClause')->willReturn('1=1');
        $GLOBALS['BE_USER'] = $backendUser;

        // No TCA: BackendUtility::readPageAccess() and getRecordTitle() don't query the database
        $tcaSchemaFactory = $this->createStub(TcaSchemaFactory::class);
        $tcaSchemaFactory->method('has')->willReturn(false);
        GeneralUtility::setContainer($this->createContainer([
            TcaSchemaFactory::class => $tcaSchemaFactory,
        ]));

        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->queueRepository = $this->createMock(QueueRepository::class);
        $this->writerQueueRepository = $this->createMock(QueueRepository::class);
        $this->queueExecutor = $this->createMock(QueueExecutor::class);

        $this->pageRepository = $this->createMock(PageRepository::class);
        $this->pageRepository->method('getPage')->willReturnCallback(
            static fn (int $uid): array => $uid === self::ROOT_PAGE_ID ? [
                'uid' => $uid,
            ] : []
        );

        $this->pageTree = $this->createMock(PageTreeView::class);
        $this->pageTree->method('getTree')->willReturnCallback(function (): int {
            $this->pageTree->tree = $this->treePages;
            return count($this->treePages);
        });
    }

    #[Test]
    public function commandIsConfigured(): void
    {
        $command = $this->createCommand();

        self::assertSame('crawler:buildQueue', $command->getName());
        self::assertSame('Create entries in the queue that can be processed at once', $command->getDescription());
        self::assertSame(
            'Try "typo3 help crawler:buildQueue" to see your options' . "\n\n"
            . 'Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;' . "\n"
            . 'It can put entries in the queue from command line options, return the list of URLs and even execute'
            . "\n"
            . 'all entries right away without having to queue them up - this can be useful for immediate re-cache,'
            . "\n"
            . 're-indexing or static publishing from command line.' . "\n\n"
            . "\n"
            . '            Examples:' . "\n"
            . '              --- Re-cache pages from page 7 and two levels down, executed immediately' . "\n"
            . '              $ typo3 crawler:buildQueue 7 defaultConfiguration --depth 2 --mode exec' . "\n"
            . "\n"
            . '              --- Put entries for re-caching pages from page 7 into queue, 4 every minute.' . "\n"
            . '              $ typo3 crawler:buildQueue 7 defaultConfiguration --depth 0 --mode queue --number 4' . "\n"
            . '            ',
            $command->getHelp()
        );

        $definition = $command->getDefinition();
        self::assertSame(2, $definition->getArgumentCount());
        self::assertTrue($definition->getArgument('page')->isRequired());
        self::assertSame(
            'The page from where the queue building should start',
            $definition->getArgument('page')->getDescription()
        );
        self::assertTrue($definition->getArgument('conf')->isRequired());
        self::assertSame(
            'A comma separated list of crawler configurations',
            $definition->getArgument('conf')->getDescription()
        );

        self::assertCount(3, $definition->getOptions());
        $depth = $definition->getOption('depth');
        self::assertSame('d', $depth->getShortcut());
        self::assertTrue($depth->isValueOptional());
        self::assertSame('0', $depth->getDefault());
        self::assertSame(
            'Tree depth, 0-99\', "How many levels under the \'page_id\' to include.',
            $depth->getDescription()
        );

        $mode = $definition->getOption('mode');
        self::assertSame('m', $mode->getShortcut());
        self::assertTrue($mode->isValueOptional());
        self::assertNull($mode->getDefault());
        self::assertSame(
            'Specifies output modes url : Will list URLs which wget could use as input. queue: Will put entries in'
            . ' the queue, to be processed by "typo3 messenger:consume crawler_high crawler crawler_low". exec: Will'
            . ' execute all entries right away!',
            $mode->getDescription()
        );

        $number = $definition->getOption('number');
        self::assertNull($number->getShortcut());
        self::assertTrue($number->isValueOptional());
        self::assertSame('0', $number->getDefault());
        self::assertSame(
            'Specifies how many items are put in the queue per minute. Only valid for output mode "queue"',
            $number->getDescription()
        );
    }

    #[Test]
    #[DataProvider('invalidPageDataProvider')]
    public function executeFailsForInvalidPage(string $page, string $expectedPageId): void
    {
        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->queueRepository->expects(self::never())->method('cleanUpOldQueueEntries');
        // Page 0 is invalid without looking it up
        $this->pageRepository->expects($expectedPageId === '0' ? self::never() : self::once())
            ->method('getPage')
            ->with((int) $expectedPageId);

        $commandTester = $this->createCommandTester(cleanUpOldQueueEntries: true, readsPageTree: false);
        $status = $this->execute($commandTester, [
            'page' => $page,
            'conf' => 'default',
            '--mode' => 'queue',
        ]);

        $message = "Page {$expectedPageId} is not a valid page, please check you root page id and try again.";
        self::assertSame(Command::FAILURE, $status);
        self::assertSame($this->format('<info>' . $message . '</info>') . PHP_EOL, $commandTester->getDisplay());

        $flashMessages = GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->getAllMessages();
        self::assertCount(1, $flashMessages);
        self::assertSame($message, $flashMessages[0]->getMessage());
        self::assertSame(ContextualFeedbackSeverity::ERROR, $flashMessages[0]->getSeverity());
    }

    public static function invalidPageDataProvider(): iterable
    {
        yield 'page 0' => ['0', '0'];
        yield 'negative page id' => ['-5', '0'];
        yield 'not a number' => ['abc', '0'];
        yield 'page does not exist' => ['2', '2'];
    }

    #[Test]
    #[DataProvider('submittingModeDataProvider')]
    public function submittingModesInvokeQueueChangeEvent(?string $mode): void
    {
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static fn (object $event): bool => $event instanceof InvokeQueueChangeEvent
                && $event->getReasonText() === Reason::REASON_CLI_SUBMIT
                && $event->getReasonDetailedText() === 'The cli script of the crawler added to the queue'))
            ->willReturnArgument(0);

        $status = $this->execute($this->createCommandTester(), array_filter([
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => 'default',
            '--mode' => $mode,
        ], static fn (?string $value): bool => $value !== null));

        self::assertSame(Command::SUCCESS, $status);
    }

    public static function submittingModeDataProvider(): iterable
    {
        yield 'queue' => ['queue'];
        yield 'exec' => ['exec'];
        yield 'queue is the default mode' => [null];
    }

    #[Test]
    #[DataProvider('nonSubmittingModeDataProvider')]
    public function nonSubmittingModesDontInvokeQueueChangeEvent(string $mode): void
    {
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $status = $this->execute($this->createCommandTester(), [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => 'default',
            '--mode' => $mode,
        ]);

        self::assertSame(Command::SUCCESS, $status);
    }

    public static function nonSubmittingModeDataProvider(): iterable
    {
        yield 'url' => ['url'];
        yield 'unknown mode' => ['list'];
    }

    #[Test]
    #[DataProvider('cleanUpOldQueueEntriesDataProvider')]
    public function oldQueueEntriesAreCleanedUpIfConfigured(bool $cleanUpOldQueueEntries): void
    {
        $this->queueRepository->expects($cleanUpOldQueueEntries ? self::once() : self::never())
            ->method('cleanUpOldQueueEntries');

        $this->execute($this->createCommandTester(cleanUpOldQueueEntries: $cleanUpOldQueueEntries), [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => 'default',
            '--mode' => 'url',
        ]);
    }

    public static function cleanUpOldQueueEntriesDataProvider(): iterable
    {
        yield 'enabled' => [true];
        yield 'disabled' => [false];
    }

    #[Test]
    #[DataProvider('depthDataProvider')]
    public function depthIsLimitedToTheRangeOfZeroToNinetyNine(string $depth, int $expectedDepth): void
    {
        $this->pageTree->expects(self::once())
            ->method('getTree')
            ->with(self::ROOT_PAGE_ID, $expectedDepth, '');

        $this->execute($this->createCommandTester(), [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => 'default',
            '--mode' => 'url',
            '--depth' => $depth,
        ]);
    }

    public static function depthDataProvider(): iterable
    {
        yield 'one level' => ['1', 1];
        yield 'maximum' => ['99', 99];
        yield 'above maximum' => ['100', 99];
    }

    #[Test]
    #[DataProvider('depthZeroDataProvider')]
    public function pageTreeIsNotReadForDepthZero(array $parameters): void
    {
        $this->pageTree->expects(self::never())->method('getTree');
        $this->treePages = [$this->getTreePage(self::ROOT_PAGE_ID)];
        $this->pageConfigurations = [
            self::ROOT_PAGE_ID => [
                'default' => $this->getConfiguration('default'),
            ],
        ];

        $commandTester = $this->createCommandTester();
        $this->execute($commandTester, [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => 'default',
            '--mode' => 'list',
            ...$parameters,
        ]);

        self::assertStringContainsString('0 entries found for processing.', $commandTester->getDisplay());
    }

    public static function depthZeroDataProvider(): iterable
    {
        yield 'default depth' => [[]];
        yield 'depth 0' => [[
            '--depth' => '0',
        ]];
        yield 'negative depth' => [[
            '--depth' => '-3',
        ]];
    }

    #[Test]
    public function urlModeListsTheUrlsForDownload(): void
    {
        $this->givenPagesWithConfigurations();
        $this->writerQueueRepository->expects(self::never())->method('add');

        $commandTester = $this->createCommandTester();
        $status = $this->execute($commandTester, [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => '',
            '--mode' => 'url',
            '--depth' => '1',
        ]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertSame(
            $this->format('<info>https://example.com/2?config=default' . PHP_EOL
            . 'https://example.com/2?config=news' . PHP_EOL
            . 'https://example.com/3?config=default' . PHP_EOL . '</info>') . PHP_EOL,
            $commandTester->getDisplay()
        );
    }

    #[Test]
    public function urlModeWritesTheUrlsInOneClosedInfoTag(): void
    {
        $this->givenPagesWithConfigurations();
        $this->addInstancesForExecution();
        $output = new MessageRecordingOutput();

        $status = $this->createCommand()->run(new ArrayInput([
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => '',
            '--mode' => 'url',
            '--depth' => '1',
        ]), $output);

        self::assertSame(Command::SUCCESS, $status);
        self::assertSame([
            '<info>https://example.com/2?config=default' . PHP_EOL
            . 'https://example.com/2?config=news' . PHP_EOL
            . 'https://example.com/3?config=default' . PHP_EOL . '</info>',
        ], $output->lines);
    }

    #[Test]
    public function queueModeAddsTheUrlsToTheQueue(): void
    {
        $this->givenPagesWithConfigurations();
        $this->writerQueueRepository->expects(self::exactly(3))->method('add')->willReturn(1);

        $commandTester = $this->createCommandTester();
        $status = $this->execute($commandTester, [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => '',
            '--mode' => 'queue',
            '--depth' => '1',
        ]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertSame(
            $this->format('<info>Putting 3 entries in queue:</info>' . PHP_EOL) . PHP_EOL
            . $this->getFormattedUrlRows(),
            $commandTester->getDisplay()
        );
    }

    #[Test]
    public function unknownModeOnlyListsTheUrls(): void
    {
        $this->givenPagesWithConfigurations();
        $this->writerQueueRepository->expects(self::never())->method('add');

        $commandTester = $this->createCommandTester();
        $status = $this->execute($commandTester, [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => '',
            '--mode' => 'list',
            '--depth' => '1',
        ]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertSame(
            $this->format(
                '<info>3 entries found for processing. (Use "mode" to decide action):</info>' . PHP_EOL
            ) . PHP_EOL
            . $this->getFormattedUrlRows(),
            $commandTester->getDisplay()
        );
    }

    #[Test]
    #[DataProvider('configurationSelectionDataProvider')]
    public function onlySelectedConfigurationsAreUsed(string $conf, array $expectedUrls): void
    {
        $this->givenPagesWithConfigurations();

        $commandTester = $this->createCommandTester();
        $this->execute($commandTester, [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => $conf,
            '--mode' => 'url',
            '--depth' => '1',
        ]);

        self::assertSame(
            $this->format('<info>' . implode(PHP_EOL, $expectedUrls) . PHP_EOL . '</info>') . PHP_EOL,
            $commandTester->getDisplay()
        );
    }

    public static function configurationSelectionDataProvider(): iterable
    {
        yield 'all configurations' => [' ', [
            'https://example.com/2?config=default',
            'https://example.com/2?config=news',
            'https://example.com/3?config=default',
        ]];
        yield 'one configuration' => ['news', ['https://example.com/2?config=news']];
        yield 'list of configurations' => [' news , other ', ['https://example.com/2?config=news']];
    }

    #[Test]
    #[DataProvider('numberDataProvider')]
    public function numberSetsTheRequestsPerMinute(string $number, string $expectedTimeOfSecondUrl): void
    {
        $this->treePages = [$this->getTreePage(2)];
        $this->pageConfigurations = [
            2 => [
                'default' => $this->getConfiguration('default'),
                'news' => $this->getConfiguration('news'),
            ],
        ];

        $commandTester = $this->createCommandTester();
        $this->execute($commandTester, [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => '',
            '--mode' => 'list',
            '--depth' => '1',
            '--number' => $number,
        ]);

        self::assertStringContainsString(
            '[' . $expectedTimeOfSecondUrl . '] https://example.com/2?config=news',
            $commandTester->getDisplay()
        );
    }

    public static function numberDataProvider(): iterable
    {
        yield 'one per minute' => ['1', '2026-11-27 10:01'];
        yield 'two per minute' => ['2', '2026-11-27 10:00'];
        yield 'below minimum' => ['-5', '2026-11-27 10:01'];
    }

    #[Test]
    #[DataProvider('requestsPerMinuteDataProvider')]
    public function requestsPerMinuteDefaultsToThirtyAndIsAtMostOneThousand(
        string $number,
        int $numberOfUrls,
        string $expectedLastUrls
    ): void {
        // The URLs are scheduled 60 / requests per minute seconds apart, rounded to full seconds.
        // The last URL is the first one of the next minute.
        $this->treePages = [$this->getTreePage(2)];
        $configuration = $this->getConfiguration('default');
        $configuration['URLs'] = array_map(
            static fn (int $index): string => 'index=' . $index,
            range(1, $numberOfUrls)
        );
        $this->pageConfigurations = [
            2 => [
                'default' => $configuration,
            ],
        ];

        $commandTester = $this->createCommandTester();
        $this->execute($commandTester, [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => '',
            '--mode' => 'list',
            '--depth' => '1',
            '--number' => $number,
        ]);

        self::assertStringEndsWith(
            $this->format('<info>' . $expectedLastUrls . '</info>') . PHP_EOL,
            $commandTester->getDisplay()
        );
    }

    public static function requestsPerMinuteDataProvider(): iterable
    {
        yield 'default of 30 per minute' => [
            '0',
            31,
            '[2026-11-27 10:00] https://example.com/2?index=30<br>[2026-11-27 10:01] https://example.com/2?index=31',
        ];
        yield 'at most 1000 per minute' => [
            '5000',
            993,
            '[2026-11-27 10:00] https://example.com/2?index=992<br>[2026-11-27 10:01] https://example.com/2?index=993',
        ];
    }

    #[Test]
    public function outputUrlsShowsWhyAPageIsNotAdded(): void
    {
        $queueRow = new QueueRow('Page title');
        $queueRow->setMessage(' (Because page is hidden)');
        $output = new MessageRecordingOutput(decorated: true);

        (new \ReflectionMethod(BuildQueueCommand::class, 'outputUrls'))
            ->invoke($this->createCommand(), [$queueRow], $output);

        self::assertSame(
            ['<comment>Page "Page title" is not added to queue (Because page is hidden)</comment>'],
            $output->lines
        );
        self::assertSame(
            $this->format(
                '<comment>Page "Page title" is not added to queue (Because page is hidden)</comment>'
            ) . PHP_EOL,
            $output->fetch()
        );
    }

    #[Test]
    public function execModeCrawlsTheUrlsRightAway(): void
    {
        $this->treePages = [$this->getTreePage(2)];
        $this->pageConfigurations = [
            2 => [
                'default' => $this->getConfiguration('default'),
                'index' => $this->getConfiguration('index', [
                    'procInstrFilter' => 'tx_indexedsearch_reindex, tx_other',
                ]),
            ],
        ];
        // The URLs are not stored in the queue by the QueueEntryWriter, but by the command
        $this->writerQueueRepository->expects(self::never())->method('add');

        $addedEntries = [];
        $this->queueRepository->expects(self::exactly(2))
            ->method('add')
            ->willReturnCallback(static function (array $queueEntry) use (&$addedEntries): int {
                $addedEntries[] = $queueEntry;
                return 100 + count($addedEntries);
            });
        $this->queueRepository->method('getQueueEntriesByQid')
            ->willReturnCallback(static function (int $queueId, bool $force) use (&$addedEntries): array {
                self::assertFalse($force);
                return [
                    'qid' => $queueId,
                    ...$addedEntries[$queueId - 101],
                ];
            });
        $this->queueExecutor->expects(self::exactly(2))
            ->method('executeQueueItem')
            ->willReturnOnConsecutiveCalls(
                [
                    'content' => json_encode([
                        'log' => ['First line', 'Second line'],
                    ]),
                ],
                [
                    'content' => json_encode([
                        'vars' => [],
                    ]),
                ],
            );

        $commandTester = $this->createCommandTester();
        $status = $this->execute($commandTester, [
            'page' => (string) self::ROOT_PAGE_ID,
            'conf' => '',
            '--mode' => 'exec',
            '--depth' => '1',
        ]);
        $display = $commandTester->getDisplay();

        self::assertSame(Command::SUCCESS, $status);
        self::assertCount(2, $addedEntries);
        self::assertSame('default', $addedEntries[0]['configuration']);
        self::assertSame('index', $addedEntries[1]['configuration']);

        self::assertStringStartsWith(
            $this->format('<info>Executing 2 requests right away:</info>') . PHP_EOL
            . $this->format('<info>' . $this->getUrlLine(2, 'default') . '</info>') . PHP_EOL
            . $this->format('<info>' . $this->getUrlLine(2, 'index') . '</info>') . PHP_EOL
            . $this->format('<info>Processing</info>' . PHP_EOL) . PHP_EOL,
            $display
        );

        // Each entry clears the progress bar, shows its URL, redraws the progress bar, clears it again for the
        // result and redraws it after the result
        self::assertMatchesRegularExpression(
            $this->getExecutedEntryPattern(
                'https://example.com/2?config=default',
                'OK: ' . chr(9) . chr(9) . 'First line' . PHP_EOL . chr(9) . chr(9) . 'Second line',
                ' 0/2 \[[>=-]+\]   0%'
            ),
            $display
        );
        self::assertMatchesRegularExpression(
            $this->getExecutedEntryPattern(
                'https://example.com/2?config=index (tx_indexedsearch_reindex,tx_other)',
                'OK: ',
                ' 1/2 \[[>=-]+\]  50%'
            ),
            $display
        );
        self::assertStringEndsWith(' 100%' . PHP_EOL, $display);
    }

    #[Test]
    #[DataProvider('processingInstructionsDataProvider')]
    public function outputModeExecShowsTheProcessingInstructionsOfTheUrl(
        array $processingInstructions,
        string $expectedSuffix
    ): void {
        $this->givenCrawlResult([
            'content' => '[]',
        ]);

        $output = $this->invokeOutputModeExec([
            [
                'url' => 'https://example.com/',
                'procInstructions' => $processingInstructions,
            ],
        ]);

        self::assertStringContainsString(
            self::CLEAR_LINE . $this->format('<info>https://example.com/' . $expectedSuffix . ' => </info>') . PHP_EOL,
            $output
        );
    }

    public static function processingInstructionsDataProvider(): iterable
    {
        yield 'none' => [[], ''];
        yield 'empty' => [[''], ''];
        yield 'one' => [['tx_indexedsearch_reindex'], ' (tx_indexedsearch_reindex)'];
        yield 'two' => [['tx_indexedsearch_reindex', 'tx_other'], ' (tx_indexedsearch_reindex,tx_other)'];
    }

    #[Test]
    #[DataProvider('invalidCrawlResultDataProvider')]
    public function outputModeExecShowsInvalidCrawlResults(mixed $result, string $expectedOutput): void
    {
        $this->givenCrawlResult($result);

        $output = $this->invokeOutputModeExec([
            [
                'url' => 'https://example.com/',
            ],
        ]);

        self::assertStringContainsString(
            self::CLEAR_LINE . $this->format(
                '<error>Error checking Crawler Result:  ' . $expectedOutput . '...' . PHP_EOL . '</error>' . PHP_EOL
            ) . PHP_EOL,
            $output
        );
    }

    public static function invalidCrawlResultDataProvider(): iterable
    {
        yield 'HTML content' => [
            [
                'content' => '<html><body><p>Fatal' . chr(10) . '   error</p></body></html>',
            ],
            'Fatal error',
        ];
        yield 'no content' => [[], ''];
        yield 'no result' => [false, ''];
        yield 'long content is shortened' => [
            [
                'content' => str_repeat('a', 30001),
            ],
            str_repeat('a', 30000),
        ];
    }

    #[Test]
    public function outputModeExecSkipsQueueEntriesWithoutParameters(): void
    {
        // Only the second entry is crawled
        $this->givenCrawlResult([
            'content' => '[]',
        ], 1);

        $output = $this->invokeOutputModeExec([
            false,
            [
                'url' => 'https://example.com/',
            ],
        ]);

        self::assertSame(1, substr_count($output, ' => '));
        self::assertStringContainsString($this->format('<info>https://example.com/ => </info>'), $output);
    }

    /**
     * Each queue entry is added to the queue as entry 1 and crawled with the given result
     */
    private function givenCrawlResult(mixed $result, int $numberOfEntries = 1): void
    {
        $this->queueRepository->expects(self::exactly($numberOfEntries))->method('add')->willReturn(1);
        $this->queueRepository->method('getQueueEntriesByQid')->willReturn([
            'qid' => 1,
            'page_id' => 2,
        ]);
        $this->queueExecutor->method('executeQueueItem')->willReturn($result);
    }

    /**
     * @param list<array|false> $parameters The parameters of each queue entry
     */
    private function invokeOutputModeExec(array $parameters): string
    {
        $run = new CrawlRun(0, 30);
        foreach ($parameters as $entryParameters) {
            $run->addQueueEntry([
                'parameters' => json_encode($entryParameters),
            ]);
        }

        $output = new BufferedOutput(decorated: true);
        (new \ReflectionMethod(BuildQueueCommand::class, 'outputModeExec'))
            ->invoke($this->createCommand(), $output, [], $run);
        return $output->fetch();
    }

    private function getExecutedEntryPattern(string $url, string $result, string $progressBarPattern): string
    {
        return '~' . preg_quote(self::CLEAR_LINE . $this->format('<info>' . $url . ' => </info>') . PHP_EOL, '~')
            . preg_quote(self::CLEAR_LINE, '~') . $progressBarPattern
            . preg_quote(self::CLEAR_LINE . $this->format('<info>' . $result . '</info>' . PHP_EOL) . PHP_EOL, '~')
            . preg_quote(self::CLEAR_LINE, '~') . $progressBarPattern . '~';
    }

    /**
     * Page 2 has the configurations "default" and "news", page 3 has "default", page 4 is skipped
     */
    private function givenPagesWithConfigurations(): void
    {
        $this->treePages = [
            $this->getTreePage(2),
            $this->getTreePage(3),
            $this->getTreePage(4, 'Because page is hidden'),
        ];
        $this->pageConfigurations = [
            2 => [
                'default' => $this->getConfiguration('default'),
                'news' => $this->getConfiguration('news'),
            ],
            3 => [
                'default' => $this->getConfiguration('default'),
            ],
        ];
    }

    /**
     * The rows of the pages of givenPagesWithConfigurations(), as written by the command
     */
    private function getFormattedUrlRows(): string
    {
        return $this->format('<info>' . $this->getUrlLine(2, 'default') . '</info>') . PHP_EOL
            . $this->format('<info>' . $this->getUrlLine(2, 'news') . '</info>') . PHP_EOL
            . $this->format('<info>' . $this->getUrlLine(3, 'default') . '</info>') . PHP_EOL
            . $this->format('<comment>Page "" is not added to queue (Because page is hidden)</comment>') . PHP_EOL;
    }

    private function getTreePage(int $uid, string $skipReason = ''): array
    {
        return [
            'row' => [
                'uid' => $uid,
                'doktype' => PageRepository::DOKTYPE_DEFAULT,
                'skipReason' => $skipReason,
            ],
            'HTML' => '<icon/>',
        ];
    }

    private function getConfiguration(string $key, array $subConfiguration = []): array
    {
        return [
            'subCfg' => [
                'key' => $key,
                ...$subConfiguration,
            ],
            'paramExpanded' => [],
            'URLs' => ['config=' . $key],
        ];
    }

    /**
     * The URL of a configuration as listed by the QueueEntryWriter, scheduled to now
     */
    private function getUrlLine(int $pageId, string $configurationKey): string
    {
        return '[2026-11-27 10:00] https://example.com/' . $pageId . '?config=' . $configurationKey;
    }

    /**
     * Formats a message like the decorated output of the command
     */
    private function format(string $message): string
    {
        return (string) (new OutputFormatter(true))->format($message);
    }

    private function execute(CommandTester $commandTester, array $input): int
    {
        return $commandTester->execute($input, [
            'decorated' => true,
        ]);
    }

    private function createCommandTester(
        bool $cleanUpOldQueueEntries = false,
        bool $readsPageTree = true
    ): CommandTester {
        $this->addInstancesForExecution($cleanUpOldQueueEntries, $readsPageTree);

        return new CommandTester($this->createCommand());
    }

    /**
     * Adds the instances one execution of the command creates with GeneralUtility::makeInstance()
     */
    private function addInstancesForExecution(bool $cleanUpOldQueueEntries = false, bool $readsPageTree = true): void
    {
        $extensionConfigurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->method('getExtensionConfiguration')->willReturn([
            'cleanUpOldQueueEntries' => $cleanUpOldQueueEntries ? '1' : '0',
        ]);
        GeneralUtility::addInstance(ExtensionConfigurationProvider::class, $extensionConfigurationProvider);
        if ($readsPageTree) {
            GeneralUtility::addInstance(PageTreeView::class, $this->pageTree);
        }
    }

    private function createCommand(): BuildQueueCommand
    {
        $clock = new FrozenClock(self::NOW);
        $pageService = $this->createStub(PageService::class);
        $pageService->method('getExclusionReason')->willReturn(false);
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        return new BuildQueueCommand(
            $this->eventDispatcher,
            $this->queueRepository,
            $this->pageRepository,
            $this->createPageTreeUrlCollector($clock),
            new CrawlUrlHandler($this->queueRepository, $this->queueExecutor, $eventDispatcher, $clock, $pageService),
            $clock,
        );
    }

    private function createPageTreeUrlCollector(FrozenClock $clock): PageTreeUrlCollector
    {
        $pageConfigurationResolver = $this->createStub(PageConfigurationResolver::class);
        $pageConfigurationResolver->method('getSkipReason')->willReturnCallback(
            static fn (array $pageRow): string => $pageRow['skipReason']
        );
        $pageConfigurationResolver->method('getConfigurationsForPage')->willReturnCallback(
            fn (int $pageId): array => $this->pageConfigurations[$pageId] ?? []
        );

        $configurationService = $this->createStub(ConfigurationService::class);
        $configurationService->method('expandExcludeString')->willReturn([]);

        $urlService = $this->createStub(UrlService::class);
        $urlService->method('getUrlFromPageAndQueryParameters')->willReturnCallback(
            static fn (int $pageId, string $query): UriInterface => new Uri(
                'https://example.com/' . $pageId . '?' . $query
            )
        );

        $extensionConfigurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->method('getExtensionConfiguration')->willReturn([
            'enableTimeslot' => '1',
        ]);
        $this->writerQueueRepository->method('getDuplicateQueueItemsIfExists')->willReturn([]);

        return new PageTreeUrlCollector(
            $this->createStub(PageRepository::class),
            $this->createStub(IconFactory::class),
            $configurationService,
            $pageConfigurationResolver,
            new QueueEntryWriter(
                $this->writerQueueRepository,
                $this->createStub(EventDispatcherInterface::class),
                $urlService,
                new ProcessInstructionService(),
                $this->createStub(CrawlUrlMessageDispatcher::class),
                $extensionConfigurationProvider,
                $clock,
            ),
        );
    }

    /**
     * @param array<class-string, object> $services
     */
    private function createContainer(array $services): ContainerInterface
    {
        return new class($services) implements ContainerInterface {
            public function __construct(
                private readonly array $services
            ) {
            }

            public function get(string $id): object
            {
                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }
        };
    }
}
