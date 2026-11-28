<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Queue;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Configuration\ExtensionSettings;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\AfterUrlAddedToQueueEvent;
use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use AOE\Crawler\Tests\Unit\FrozenClock;
use AOE\Crawler\Value\QueueEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(QueueEntryWriter::class)]
class QueueEntryWriterTest extends UnitTestCase
{
    #[Test]
    public function getConfigurationHashIgnoresExpandedParametersAndUrls(): void
    {
        $configuration = [
            'paramExpanded' => 'extendedParameter',
            'URLs' => 'URLs',
            'NotImportantParameter' => 'value not important',
        ];

        $originalCheckSum = md5(serialize($configuration));

        self::assertNotEquals($originalCheckSum, $this->getConfigurationHash($configuration));

        unset($configuration['paramExpanded'], $configuration['URLs']);
        $newCheckSum = md5(serialize($configuration));
        self::assertEquals($newCheckSum, $this->getConfigurationHash($configuration));
    }

    #[DataProvider('getConfigurationHashReturnsExpectedValueDataProvider')]
    #[Test]
    public function getConfigurationHashReturnsExpectedValue(array $configuration, string $expected): void
    {
        self::assertEquals($expected, $this->getConfigurationHash($configuration));
    }

    public static function getConfigurationHashReturnsExpectedValueDataProvider(): iterable
    {
        yield 'Configuration with either paramExpanded nor URLs set' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => '',
                'URLs' => '',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
        yield 'Configuration with only paramExpanded set' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => 'Value not important',
                'URLs' => '',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
        yield 'Configuration with only URLS set' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => '',
                'URLs' => 'Value not important',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
        yield 'Configuration with both paramExpanded and URLS set' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => 'Value not important',
                'URLs' => 'Value not important',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
        yield 'Configuration with both paramExpanded and URLS set, will return same hash' => [
            'configuration' => [
                'testKey' => 'testValue',
                'paramExpanded' => 'Value not important, but different than test case before',
                'URLs' => 'Value not important, but different than test case before',
            ],
            'expected' => 'a73d2e7035f7fa032237c8cf0eb5be22',
        ];
    }

    #[Test]
    public function compileUrlListCollectsDownloadUrlsAndSkipsUrlsAlreadyHandledInTheRun(): void
    {
        $run = new CrawlRun(1_000_000, 30, collectDownloadUrls: true);
        $subject = $this->createSubjectForCompileUrlList();
        $configuration = $this->getConfiguration(['L=0', 'L=1']);

        $firstUrls = $subject->compileUrlList($configuration, [
            'uid' => '5',
        ], $run);
        $urls = $subject->compileUrlList($configuration, [
            'uid' => '5',
        ], $run);

        self::assertSame([
            'https://example.com/?L=0' => 'https://example.com/?L=0',
            'https://example.com/?L=1' => 'https://example.com/?L=1',
        ], $run->getDownloadUrls());
        self::assertSame(2, $run->countHandledUrls());
        self::assertCount(2, $run->getUrlList());
        $formattedDate = date('Y-m-d H:i', $run->scheduledTime - $run->scheduledTime % 60);
        self::assertSame(
            '[' . $formattedDate . '] https://example.com/?L=0<br>[' . $formattedDate . '] https://example.com/?L=1',
            $firstUrls
        );
        self::assertSame(
            '<em><span class="text-muted">https://example.com/?L=0</span></em><br>'
            . '<em><span class="text-muted">https://example.com/?L=1</span></em>',
            $urls
        );
    }

    #[Test]
    public function compileUrlListSpreadsScheduledTimeByRequestsPerMinute(): void
    {
        $run = new CrawlRun(1_000_050, 1);

        $this->createSubjectForCompileUrlList()->compileUrlList($this->getConfiguration(['L=0', 'L=1']), [
            'uid' => 5,
        ], $run);

        self::assertSame([
            '[' . date('Y-m-d H:i', 1_000_020) . '] https://example.com/?L=0',
            '[' . date('Y-m-d H:i', 1_000_080) . '] https://example.com/?L=1',
        ], $run->getUrlList());
        self::assertSame([], $run->getDownloadUrls());
    }

    #[Test]
    public function compileUrlListSkipsUrlsOfOtherProcessingInstructions(): void
    {
        $run = new CrawlRun(1_000_000, 30, collectDownloadUrls: true, processingInstructions: ['tx_other']);

        $urls = $this->createSubjectForCompileUrlList()->compileUrlList(
            $this->getConfiguration(['L=0'], 'tx_indexedsearch_reindex'),
            [
                'uid' => 5,
            ],
            $run
        );

        self::assertSame('', $urls);
        self::assertSame(0, $run->countHandledUrls());
        self::assertSame([], $run->getDownloadUrls());
    }

    #[Test]
    public function compileUrlListUsesUrlsOfMatchingProcessingInstructions(): void
    {
        $run = new CrawlRun(1_000_000, 30, processingInstructions: ['tx_indexedsearch_reindex']);

        $this->createSubjectForCompileUrlList()->compileUrlList(
            $this->getConfiguration(['L=0'], 'tx_indexedsearch_reindex'),
            [
                'uid' => 5,
            ],
            $run
        );

        self::assertTrue($run->isUrlHandled('https://example.com/?L=0', '', 'tx_indexedsearch_reindex'));
    }

    #[Test]
    public function compileUrlListHandlesUrlOncePerUserGroupsAndProcessingInstructionFilter(): void
    {
        $run = new CrawlRun(1_000_000, 30);
        $subject = $this->createSubjectForCompileUrlList();
        $pageRow = [
            'uid' => 5,
        ];

        $subject->compileUrlList($this->getConfiguration(['L=0']), $pageRow, $run);
        $subject->compileUrlList($this->getConfiguration(['L=0'], '', '1,2'), $pageRow, $run);
        $subject->compileUrlList($this->getConfiguration(['L=0'], 'tx_indexedsearch_reindex'), $pageRow, $run);
        $subject->compileUrlList($this->getConfiguration(['L=0'], 'tx_indexedsearch_reindex', '1,2'), $pageRow, $run);

        self::assertSame(4, $run->countHandledUrls());
        self::assertTrue($run->isUrlHandled('https://example.com/?L=0', '1,2', 'tx_indexedsearch_reindex'));
    }

    #[Test]
    public function compileUrlListSkipsQueryParametersWithoutUrl(): void
    {
        $run = new CrawlRun(1_000_000, 30, collectDownloadUrls: true);

        $urls = $this->createSubjectForCompileUrlList()->compileUrlList($this->getConfiguration(['none', 'L=1']), [
            'uid' => 5,
        ], $run);

        self::assertSame(1, $run->countHandledUrls());
        self::assertSame([
            'https://example.com/?L=1' => 'https://example.com/?L=1',
        ], $run->getDownloadUrls());
        self::assertStringNotContainsString('none', $urls);
    }

    #[Test]
    public function compileUrlListBuildsUrlsWithBaseUrlAndForceSsl(): void
    {
        $urlService = $this->createMock(UrlService::class);
        $urlService->expects(self::exactly(2))
            ->method('getUrlFromPageAndQueryParameters')
            ->willReturnCallback(static function (
                int $pageId,
                string $query,
                ?string $baseUrl,
                int $forceSsl
            ): UriInterface {
                self::assertSame(5, $pageId);
                self::assertSame('L=0', $query);
                return new Uri(($baseUrl ?? 'https://example.com/') . '?force_ssl=' . $forceSsl);
            });
        $subject = $this->createSubjectForCompileUrlList($urlService);
        $run = new CrawlRun(1_000_000, 30, collectDownloadUrls: true);

        $subject->compileUrlList($this->getConfiguration(['L=0']), [
            'uid' => 5,
        ], $run);
        $configuration = $this->getConfiguration(['L=0']);
        $configuration['subCfg']['baseUrl'] = 'https://base.example.com/';
        $configuration['subCfg']['force_ssl'] = '1';
        $subject->compileUrlList($configuration, [
            'uid' => 5,
        ], $run);

        self::assertSame([
            'https://example.com/?force_ssl=0',
            'https://base.example.com/?force_ssl=1',
        ], array_values($run->getDownloadUrls()));
    }

    #[Test]
    public function compileUrlListReturnsErrorIfConfigurationHasNoUrls(): void
    {
        $run = new CrawlRun(1_000_000, 30, collectDownloadUrls: true);

        $urls = $this->createSubjectForCompileUrlList()->compileUrlList([
            'subCfg' => [
                'key' => 'default',
            ],
            'URLs' => null,
        ], [
            'uid' => 5,
        ], $run);

        self::assertSame('ERROR - no URL generated', $urls);
        self::assertSame(0, $run->countHandledUrls());
    }

    #[Test]
    public function compileUrlListSubmitsUrlsToTheQueue(): void
    {
        $configuration = $this->getConfiguration(['L=0']);
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::once())
            ->method('noUnprocessedQueueEntriesForPageWithConfigurationHashExist')
            ->with(5, $this->getConfigurationHash($configuration))
            ->willReturn(false);
        $queueRepository->expects(self::once())->method('getDuplicateQueueItemsIfExists')->willReturn([]);
        $queueRepository->expects(self::once())->method('add')
            ->with(self::callback(static function (array $fieldArray) use ($configuration): bool {
                self::assertSame(5, $fieldArray['page_id']);
                self::assertSame(1_000_000, $fieldArray['scheduled']);
                self::assertSame(md5(serialize([
                    'subCfg' => $configuration['subCfg'],
                ])), $fieldArray['configuration_hash']);
                return true;
            }))
            ->willReturn(12);
        $run = new CrawlRun(1_000_000, 30, submitUrls: true, setId: 99);

        $urls = $this->createSubjectForCompileUrlList(queueRepository: $queueRepository)
            ->compileUrlList($configuration, [
                'uid' => '5',
            ], $run);

        self::assertStringEndsWith('] https://example.com/?L=0', $urls);
        self::assertStringNotContainsString('URL already existed', $urls);
        self::assertSame([], $run->getDownloadUrls());
    }

    #[Test]
    public function compileUrlListMarksUrlsThatAlreadyExistInTheQueue(): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->method('noUnprocessedQueueEntriesForPageWithConfigurationHashExist')->willReturn(false);
        $queueRepository->expects(self::once())->method('getDuplicateQueueItemsIfExists')->willReturn([
            [
                'qid' => 3,
            ],
        ]);
        $queueRepository->expects(self::never())->method('add');
        $run = new CrawlRun(1_000_000, 30, submitUrls: true);

        $urls = $this->createSubjectForCompileUrlList(queueRepository: $queueRepository)
            ->compileUrlList($this->getConfiguration(['L=0']), [
                'uid' => 5,
            ], $run);

        self::assertStringEndsWith('] https://example.com/?L=0 (URL already existed)', $urls);
    }

    #[Test]
    public function compileUrlListDoesNotMarkUrlsAsExistingIfQueueEntriesAreOnlyCollected(): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->method('noUnprocessedQueueEntriesForPageWithConfigurationHashExist')->willReturn(false);
        $queueRepository->expects(self::never())->method('add');
        $run = new CrawlRun(1_000_000, 30, submitUrls: true, collectQueueEntriesOnly: true);

        $urls = $this->createSubjectForCompileUrlList(queueRepository: $queueRepository)
            ->compileUrlList($this->getConfiguration(['L=0']), [
                'uid' => 5,
            ], $run);

        self::assertStringEndsWith('] https://example.com/?L=0', $urls);
        self::assertCount(1, $run->getQueueEntries());
    }

    #[Test]
    public function compileUrlListSkipsDuplicateCheckIfNoUnprocessedEntriesOfTheConfigurationExist(): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->method('noUnprocessedQueueEntriesForPageWithConfigurationHashExist')->willReturn(true);
        $queueRepository->expects(self::never())->method('getDuplicateQueueItemsIfExists');
        $queueRepository->expects(self::exactly(2))->method('add')->willReturn(1);
        $run = new CrawlRun(1_000_000, 30, submitUrls: true);

        $this->createSubjectForCompileUrlList(queueRepository: $queueRepository)
            ->compileUrlList($this->getConfiguration(['L=0', 'L=1']), [
                'uid' => 5,
            ], $run);
    }

    #[Test]
    public function addUrlAddsQueueEntryAndDispatchesEventAndMessage(): void
    {
        $expectedParameters = '{"url":"https:\/\/example.com\/","feUserGroupList":"2,1",'
            . '"procInstructions":["tx_a","tx_b"],"procInstrParams":{"key":"value"}}';
        $expectedFieldArray = [
            'page_id' => 5,
            'parameters' => $expectedParameters,
            'parameters_hash' => md5($expectedParameters),
            'configuration_hash' => 'hash',
            'scheduled' => 1_000_060,
            'exec_time' => 0,
            'set_id' => 99,
            'result_data' => '',
            'configuration' => 'default',
        ];
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::once())->method('getDuplicateQueueItemsIfExists')
            ->with(true, 1_000_060, 1_000_000, 5, md5($expectedParameters))
            ->willReturn([]);
        $queueRepository->expects(self::once())->method('add')->with($expectedFieldArray)->willReturn(17);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())->method('dispatch')->with(
            self::callback(static function (object $event) use ($expectedFieldArray): bool {
                self::assertInstanceOf(AfterUrlAddedToQueueEvent::class, $event);
                self::assertSame('17', $event->getUid());
                self::assertSame($expectedFieldArray, $event->getFieldArray());
                return true;
            })
        )->willReturnArgument(0);
        $crawlUrlMessageDispatcher = $this->createMock(CrawlUrlMessageDispatcher::class);
        $crawlUrlMessageDispatcher->expects(self::once())->method('dispatch')
            ->with(new CrawlUrlMessage(17), 5, 60);
        $subject = $this->createSubjectForCompileUrlList(
            queueRepository: $queueRepository,
            eventDispatcher: $eventDispatcher,
            crawlUrlMessageDispatcher: $crawlUrlMessageDispatcher,
        );

        $added = $subject->addUrl(new CrawlRun(1_000_000, 30, setId: 99), 5, 'https://example.com/', [
            'key' => 'default',
            'userGroups' => '2,,1,2',
            'procInstrFilter' => 'tx_a, tx_b',
            'procInstrParams.' => [
                'key' => 'value',
            ],
        ], 1_000_060, 'hash');

        self::assertTrue($added);
    }

    #[Test]
    public function addUrlWithoutUserGroupsAndProcessingInstructionsChecksDuplicatesWithoutTimeslot(): void
    {
        $expectedParameters = '{"url":"https:\/\/example.com\/","procInstructions":[""]}';
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::once())->method('getDuplicateQueueItemsIfExists')
            ->with(false, 1_000_000, 1_000_000, 5, md5($expectedParameters))
            ->willReturn([]);
        $queueRepository->expects(self::once())->method('add')->with(self::callback(
            static fn (array $fieldArray): bool => $fieldArray['parameters'] === $expectedParameters
                && $fieldArray['configuration_hash'] === ''
        ))->willReturn(18);
        $crawlUrlMessageDispatcher = $this->createMock(CrawlUrlMessageDispatcher::class);
        $crawlUrlMessageDispatcher->expects(self::once())->method('dispatch')
            ->with(new CrawlUrlMessage(18), 5, 0);
        $subject = $this->createSubjectForCompileUrlList(
            queueRepository: $queueRepository,
            crawlUrlMessageDispatcher: $crawlUrlMessageDispatcher,
            enableTimeslot: false,
        );

        self::assertTrue($subject->addUrl(new CrawlRun(1_000_000, 30), 5, 'https://example.com/', [
            'key' => 'default',
            'userGroups' => '0',
        ], 1_000_000));
    }

    #[Test]
    public function addUrlDoesNotAddDuplicates(): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->method('getDuplicateQueueItemsIfExists')->willReturn([
            [
                'qid' => 3,
            ],
        ]);
        $queueRepository->expects(self::never())->method('add');
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::never())->method('dispatch');
        $crawlUrlMessageDispatcher = $this->createMock(CrawlUrlMessageDispatcher::class);
        $crawlUrlMessageDispatcher->expects(self::never())->method('dispatch');
        $subject = $this->createSubjectForCompileUrlList(
            queueRepository: $queueRepository,
            eventDispatcher: $eventDispatcher,
            crawlUrlMessageDispatcher: $crawlUrlMessageDispatcher,
        );

        self::assertFalse($subject->addUrl(new CrawlRun(1_000_000, 30), 5, 'https://example.com/', [
            'key' => 'default',
        ], 1_000_000));
    }

    #[Test]
    public function addUrlSkipsDuplicateCheckIfRequested(): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::never())->method('getDuplicateQueueItemsIfExists');
        $queueRepository->expects(self::once())->method('add')->willReturn(19);
        $subject = $this->createSubjectForCompileUrlList(queueRepository: $queueRepository);

        self::assertTrue($subject->addUrl(new CrawlRun(1_000_000, 30), 5, 'https://example.com/', [
            'key' => 'default',
        ], 1_000_000, 'hash', true));
    }

    #[Test]
    public function addUrlOnlyCollectsQueueEntriesIfRequested(): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::never())->method('getDuplicateQueueItemsIfExists');
        $queueRepository->expects(self::never())->method('add');
        $run = new CrawlRun(1_000_000, 30, collectQueueEntriesOnly: true, setId: 99);

        $added = $this->createSubjectForCompileUrlList(queueRepository: $queueRepository)
            ->addUrl($run, 5, 'https://example.com/', [
                'key' => 'default',
            ], 1_000_000, 'hash');

        self::assertFalse($added);
        $expectedParameters = '{"url":"https:\/\/example.com\/","procInstructions":[""]}';
        self::assertEquals([
            new QueueEntry(
                pageId: 5,
                parameters: $expectedParameters,
                parametersHash: md5($expectedParameters),
                configurationHash: 'hash',
                scheduled: 1_000_000,
                setId: 99,
                configuration: 'default',
            ),
        ], $run->getQueueEntries());
    }

    #[Test]
    public function addCallbackAddsQueueEntryScheduledNowAndDispatchesMessage(): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::once())->method('add')->with([
            'page_id' => 0,
            'parameters' => '{"_CALLBACKOBJ":"Vendor\\\\Callback"}',
            'parameters_hash' => '',
            'configuration_hash' => '',
            'scheduled' => 1_000_000,
            'exec_time' => 0,
            'set_id' => 0,
            'result_data' => '',
            'configuration' => '',
        ])->willReturn(21);
        $crawlUrlMessageDispatcher = $this->createMock(CrawlUrlMessageDispatcher::class);
        $crawlUrlMessageDispatcher->expects(self::once())->method('dispatch')
            ->with(new CrawlUrlMessage(21), 0, 0);
        $subject = $this->createSubjectForCompileUrlList(
            queueRepository: $queueRepository,
            crawlUrlMessageDispatcher: $crawlUrlMessageDispatcher,
        );

        self::assertSame(21, $subject->addCallback('Vendor\\Callback'));
    }

    #[Test]
    public function addCallbackAddsQueueEntryWithParametersPageSetAndScheduledTime(): void
    {
        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::once())->method('add')->with([
            'page_id' => 5,
            'parameters' => '{"foo":"bar","_CALLBACKOBJ":"Vendor\\\\Callback"}',
            'parameters_hash' => '',
            'configuration_hash' => '',
            'scheduled' => 1_000_120,
            'exec_time' => 0,
            'set_id' => 7,
            'result_data' => '',
            'configuration' => '',
        ])->willReturn(22);
        $crawlUrlMessageDispatcher = $this->createMock(CrawlUrlMessageDispatcher::class);
        $crawlUrlMessageDispatcher->expects(self::once())->method('dispatch')
            ->with(new CrawlUrlMessage(22), 5, 120);
        $subject = $this->createSubjectForCompileUrlList(
            queueRepository: $queueRepository,
            crawlUrlMessageDispatcher: $crawlUrlMessageDispatcher,
        );

        self::assertSame(22, $subject->addCallback('Vendor\\Callback', [
            'foo' => 'bar',
        ], 5, 1_000_120, 7));
    }

    private function createSubjectForCompileUrlList(
        ?UrlService $urlService = null,
        ?QueueRepository $queueRepository = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?CrawlUrlMessageDispatcher $crawlUrlMessageDispatcher = null,
        bool $enableTimeslot = true,
    ): QueueEntryWriter {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'Y-m-d';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] = 'H:i';

        if (!$urlService instanceof \AOE\Crawler\Service\UrlService) {
            $urlService = $this->createStub(UrlService::class);
            $urlService->method('getUrlFromPageAndQueryParameters')->willReturnCallback(
                static fn (int $pageId, string $query): ?UriInterface => $query === 'none'
                    ? null
                    : new Uri('https://example.com/?' . $query)
            );
        }

        $extensionConfigurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->method('getExtensionSettings')->willReturn(
            new ExtensionSettings(enableTimeslot: $enableTimeslot)
        );

        return new QueueEntryWriter(
            $queueRepository ?? $this->createStub(QueueRepository::class),
            $eventDispatcher ?? $this->createStub(EventDispatcherInterface::class),
            $urlService,
            new ProcessInstructionService(),
            $crawlUrlMessageDispatcher ?? $this->createStub(CrawlUrlMessageDispatcher::class),
            $extensionConfigurationProvider,
            new FrozenClock(1_000_000),
        );
    }

    private function getConfigurationHash(array $configuration): string
    {
        return (new \ReflectionMethod(QueueEntryWriter::class, 'getConfigurationHash'))
            ->invoke($this->createSubjectForCompileUrlList(), $configuration);
    }

    private function getConfiguration(array $urls, string $procInstrFilter = '', ?string $userGroups = null): array
    {
        $subConfiguration = [
            'key' => 'default',
        ];
        if ($procInstrFilter !== '') {
            $subConfiguration['procInstrFilter'] = $procInstrFilter;
        }
        if ($userGroups !== null) {
            $subConfiguration['userGroups'] = $userGroups;
        }
        return [
            'subCfg' => $subConfiguration,
            'URLs' => $urls,
        ];
    }
}
