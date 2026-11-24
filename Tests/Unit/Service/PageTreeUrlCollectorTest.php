<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\PageTreeUrlCollector;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use AOE\Crawler\Tests\Unit\FrozenClock;
use AOE\Crawler\Value\QueueRow;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PageTreeUrlCollector::class)]
final class PageTreeUrlCollectorTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $configuration = [
            'maxCompileUrls' => '10000',
            'crawlHiddenPages' => '0',
            'enableTimeslot' => '1',
            'makeDirectRequests' => '0',
            'frontendBasePath' => '/',
            'cleanUpOldQueueEntries' => '1',
            'cleanUpProcessedAge' => '2',
            'cleanUpScheduledAge' => '7',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueueRowsForPageShowsSkipMessageWhenPageHasNoConfigurations(): void
    {
        $pageTreeUrlCollector = $this->createCollectorForRows([], 'Because page is hidden');

        $expected = new QueueRow('Page title');
        $expected->setPageTitleHTML('<icon/>');
        $expected->setMessage(' (Because page is hidden)');

        self::assertEquals(
            [$expected],
            $pageTreeUrlCollector->getQueueRowsForPage(new CrawlRun(0, 30), [
                'uid' => 5,
            ], 'Page title', '<icon/>')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueueRowsForPageShowsEmptyMessageWhenPageHasNoConfigurationsAndIsNotSkipped(): void
    {
        $pageTreeUrlCollector = $this->createCollectorForRows([]);

        $expected = new QueueRow('Page title');
        $expected->setPageTitleHTML('<icon/>');

        self::assertEquals(
            [$expected],
            $pageTreeUrlCollector->getQueueRowsForPage(new CrawlRun(0, 30), [
                'uid' => 5,
            ], 'Page title', '<icon/>')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueueRowsForPageMarksExcludedPages(): void
    {
        $pageTreeUrlCollector = $this->createCollectorForRows([
            'default' => $this->getConfiguration('default', [
                'exclude' => '3,5',
            ]),
        ]);

        $expected = new QueueRow('Page title');
        $expected->setPageTitleHTML('<icon/>');
        $expected->setConfigurationKey('default');
        $expected->setMessage('(Page is excluded in this configuration)');

        self::assertEquals(
            [$expected],
            $pageTreeUrlCollector->getQueueRowsForPage(new CrawlRun(0, 30), [
                'uid' => 5,
            ], 'Page title', '<icon/>')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueueRowsForPageAddsOneRowPerConfiguration(): void
    {
        $procInstrParams = [
            'tx_indexedsearch_reindex.' => [
                'mode' => 'full',
            ],
        ];
        $pageTreeUrlCollector = $this->createCollectorForRows([
            'default' => $this->getConfiguration('default', [
                'userGroups' => '1,2',
                'procInstrFilter' => 'tx_indexedsearch_reindex',
                'procInstrParams.' => $procInstrParams,
            ], [
                'L' => ['0', '1'],
                'tx_news[news]' => ['<b>'],
            ], [
                'L' => '[0|1]',
                'S' => 'x',
            ]),
            'news' => $this->getConfiguration('news'),
        ]);

        $default = new QueueRow('Page title');
        $default->setPageTitleHTML('<icon/>');
        $default->setConfigurationKey('default');
        $default->setUrls($this->getUrls('default'));
        $default->setOptions(['User Groups: 1,2', 'ProcInstr: tx_indexedsearch_reindex']);
        $default->setParameters(DebugUtility::viewArray($procInstrParams));
        $default->setParameterConfig('&amp;L=[0|1]<br />' . chr(10) . '&amp;S=x');
        $default->setValuesExpanded(
            '<table>' . $this->getExpandedParameterRow('&amp;L=', 2, '0<br />' . chr(10) . '1')
            . $this->getExpandedParameterRow('&amp;tx_news[news]=', 1, '&lt;b&gt;')
            . '</table>Comb: 2*1=2'
        );

        $news = new QueueRow();
        $news->setPageTitleHTML('<icon/>');
        $news->setConfigurationKey('news');
        $news->setUrls($this->getUrls('news'));
        $news->setParameters(DebugUtility::viewArray([]));
        $news->setValuesExpanded('Comb: =1');

        self::assertEquals(
            [$default, $news],
            $pageTreeUrlCollector->getQueueRowsForPage(new CrawlRun(0, 30), [
                'uid' => 5,
            ], 'Page title', '<icon/>')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueueRowsForPageOnlyShowsSelectedConfigurations(): void
    {
        $pageTreeUrlCollector = $this->createCollectorForRows([
            'default' => $this->getConfiguration('default'),
            'news' => $this->getConfiguration('news'),
        ]);
        $run = new CrawlRun(0, 30, configurationSelection: ['news']);

        $rows = $pageTreeUrlCollector->getQueueRowsForPage($run, [
            'uid' => 5,
        ], 'Page title');

        self::assertCount(1, $rows);
        self::assertSame('news', $rows[0]->configurationKey);
        self::assertSame('Page title', $rows[0]->pageTitle);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueueRowsForPageUsesNumericConfigurationKeysAsString(): void
    {
        $pageTreeUrlCollector = $this->createCollectorForRows([
            7 => $this->getConfiguration('seven'),
        ]);

        $rows = $pageTreeUrlCollector->getQueueRowsForPage(new CrawlRun(0, 30), [
            'uid' => 5,
        ], 'Page title');

        self::assertSame('7', $rows[0]->configurationKey);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueueRowsForPageResolvesConfigurationsWithMountPoint(): void
    {
        $pageConfigurationResolver = $this->createMock(PageConfigurationResolver::class);
        $pageConfigurationResolver->method('getSkipReason')->willReturn('');
        $pageConfigurationResolver->expects(self::once())
            ->method('getConfigurationsForPage')
            ->with(5, '3-7')
            ->willReturn([]);

        $pageTreeUrlCollector = new PageTreeUrlCollector(
            $this->createStub(PageRepository::class),
            $this->createStub(IconFactory::class),
            $this->createStub(ConfigurationService::class),
            $pageConfigurationResolver,
            $this->createQueueEntryWriter(),
        );

        $pageTreeUrlCollector->getQueueRowsForPage(new CrawlRun(0, 30), [
            'uid' => 5,
        ], 'Page title', '', '3-7');
    }

    private function createCollectorForRows(
        array $configurations,
        string|false $skipMessage = false
    ): PageTreeUrlCollector {
        $pageConfigurationResolver = $this->createStub(PageConfigurationResolver::class);
        $pageConfigurationResolver->method('getSkipReason')->willReturn($skipMessage === false ? '' : $skipMessage);
        $pageConfigurationResolver->method('getConfigurationsForPage')->willReturn($configurations);

        $configurationService = $this->createStub(ConfigurationService::class);
        $configurationService->method('expandExcludeString')->willReturnCallback(
            static fn (string $exclude): array => array_map(
                intval(...),
                GeneralUtility::intExplode(',', $exclude, true)
            )
        );

        return new PageTreeUrlCollector(
            $this->createStub(PageRepository::class),
            $this->createStub(IconFactory::class),
            $configurationService,
            $pageConfigurationResolver,
            $this->createQueueEntryWriter(),
        );
    }

    /**
     * A QueueEntryWriter that builds the URL "https://example.com/?<query>" of each query of a configuration
     */
    private function createQueueEntryWriter(): QueueEntryWriter
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'Y-m-d';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] = 'H:i';

        $urlService = $this->createStub(UrlService::class);
        $urlService->method('getUrlFromPageAndQueryParameters')->willReturnCallback(
            static fn (int $pageId, string $query): UriInterface => new Uri('https://example.com/?' . $query)
        );

        return new QueueEntryWriter(
            $this->createStub(QueueRepository::class),
            $this->createStub(EventDispatcherInterface::class),
            $urlService,
            new ProcessInstructionService(),
            $this->createStub(CrawlUrlMessageDispatcher::class),
            $this->createStub(ExtensionConfigurationProvider::class),
            new FrozenClock(0),
        );
    }

    /**
     * The URL list QueueEntryWriter::compileUrlList() returns for the URL of a configuration, scheduled to 0
     */
    private function getUrls(string $configurationKey): string
    {
        return '[' . date('Y-m-d H:i', 0) . '] https://example.com/?config=' . $configurationKey;
    }

    private function getConfiguration(
        string $key,
        array $subConfiguration = [],
        array $paramExpanded = [],
        array $paramParsed = []
    ): array {
        return [
            'subCfg' => [
                'key' => $key,
                ...$subConfiguration,
            ],
            'paramParsed' => $paramParsed,
            'paramExpanded' => $paramExpanded,
            'URLs' => ['config=' . $key],
        ];
    }

    private function getExpandedParameterRow(string $parameter, int $count, string $values): string
    {
        return '
                            <tr>
                                <td>' . $parameter . '<br/>(' . $count . ')</td>
                                <td nowrap="nowrap">' . $values . '</td>
                            </tr>
                        ';
    }
}
