<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Controller;

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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Value\QueueRow;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @package AOE\Crawler\Tests
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Controller\CrawlerController::class)]
class CrawlerControllerTest extends UnitTestCase
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
            'phpPath' => '/usr/bin/php',
            'enableTimeslot' => '1',
            'makeDirectRequests' => '0',
            'frontendBasePath' => '/',
            'cleanUpOldQueueEntries' => '1',
            'cleanUpProcessedAge' => '2',
            'cleanUpScheduledAge' => '7',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
    }

    public function getConfigurationKeysDataProvider(): iterable
    {
        yield 'cliObject with no -conf' => [
            'config' => [
                '-d' => 4,
                '-o' => 'url',
            ],
            'expected' => [],
        ];
        yield 'cliObject with one -conf' => [
            'config' => [
                '-d' => 4,
                '-o' => 'url',
                '-conf' => 'default',
            ],
            'expected' => ['default'],
        ];
        yield 'cliObject with two -conf' => [
            'config' => [
                '-d' => 4,
                '-o' => 'url',
                '-conf' => 'default,news',
            ],
            'expected' => ['default', 'news'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function drawURLsAddRowsForPageShowsSkipMessageWhenPageHasNoConfigurations(): void
    {
        $crawlerController = $this->createControllerForRows([], 'Because page is hidden');

        $expected = new QueueRow('Page title');
        $expected->setPageTitleHTML('<icon/>');
        $expected->setMessage(' (Because page is hidden)');

        self::assertEquals(
            [$expected],
            $crawlerController->drawURLs_addRowsForPage([
                'uid' => 5,
            ], 'Page title', '<icon/>')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function drawURLsAddRowsForPageShowsEmptyMessageWhenPageHasNoConfigurationsAndIsNotSkipped(): void
    {
        $crawlerController = $this->createControllerForRows([]);

        $expected = new QueueRow('Page title');
        $expected->setPageTitleHTML('<icon/>');

        self::assertEquals(
            [$expected],
            $crawlerController->drawURLs_addRowsForPage([
                'uid' => 5,
            ], 'Page title', '<icon/>')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function drawURLsAddRowsForPageMarksExcludedPages(): void
    {
        $crawlerController = $this->createControllerForRows([
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
            $crawlerController->drawURLs_addRowsForPage([
                'uid' => 5,
            ], 'Page title', '<icon/>')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function drawURLsAddRowsForPageAddsOneRowPerConfiguration(): void
    {
        $procInstrParams = [
            'tx_indexedsearch_reindex.' => [
                'mode' => 'full',
            ],
        ];
        $crawlerController = $this->createControllerForRows([
            'default' => $this->getConfiguration('default', [
                'userGroups' => '1,2',
                'procInstrFilter' => 'tx_indexedsearch_reindex',
                'procInstrParams.' => $procInstrParams,
            ], [
                'L' => ['0', '1'],
                'tx_news[news]' => ['<b>'],
            ], [
                'L' => '[0|1]',
            ]),
            'news' => $this->getConfiguration('news'),
        ]);

        $default = new QueueRow('Page title');
        $default->setPageTitleHTML('<icon/>');
        $default->setConfigurationKey('default');
        $default->setUrls('URLs of default');
        $default->setOptions(['User Groups: 1,2', 'ProcInstr: tx_indexedsearch_reindex']);
        $default->setParameters(DebugUtility::viewArray($procInstrParams));
        $default->setParameterConfig('&amp;L=[0|1]');
        $default->setValuesExpanded(
            '<table>' . $this->getExpandedParameterRow('&amp;L=', 2, '0<br />' . chr(10) . '1')
            . $this->getExpandedParameterRow('&amp;tx_news[news]=', 1, '&lt;b&gt;')
            . '</table>Comb: 2*1=2'
        );

        $news = new QueueRow();
        $news->setPageTitleHTML('<icon/>');
        $news->setConfigurationKey('news');
        $news->setUrls('URLs of news');
        $news->setParameters(DebugUtility::viewArray([]));
        $news->setValuesExpanded('Comb: =1');

        self::assertEquals(
            [$default, $news],
            $crawlerController->drawURLs_addRowsForPage([
                'uid' => 5,
            ], 'Page title', '<icon/>')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function drawURLsAddRowsForPageOnlyShowsSelectedConfigurations(): void
    {
        $crawlerController = $this->createControllerForRows([
            'default' => $this->getConfiguration('default'),
            'news' => $this->getConfiguration('news'),
        ]);
        $crawlerController->incomingConfigurationSelection = ['news'];

        $rows = $crawlerController->drawURLs_addRowsForPage([
            'uid' => 5,
        ], 'Page title');

        self::assertCount(1, $rows);
        self::assertSame('news', $rows[0]->configurationKey);
        self::assertSame('Page title', $rows[0]->pageTitle);
    }

    private function createControllerForRows(
        array $configurations,
        string|false $skipMessage = false
    ): CrawlerController {
        $pageConfigurationResolver = $this->createStub(PageConfigurationResolver::class);
        $pageConfigurationResolver->method('getConfigurationsForPageRow')->willReturnCallback(
            static function (array $pageRow, string &$pageSkipMessage) use ($configurations, $skipMessage): array {
                $pageSkipMessage = $skipMessage === false ? '' : $skipMessage;
                return $skipMessage === false ? $configurations : [];
            }
        );

        $configurationService = $this->createStub(ConfigurationService::class);
        $configurationService->method('expandExcludeString')->willReturnCallback(
            static fn (string $exclude): array => array_map(
                intval(...),
                GeneralUtility::intExplode(',', $exclude, true)
            )
        );

        $queueEntryWriter = $this->createStub(QueueEntryWriter::class);
        $queueEntryWriter->method('compileUrlList')->willReturnCallback(
            static fn (array $configuration): string => 'URLs of ' . $configuration['subCfg']['key']
        );

        return new CrawlerController(
            $this->createStub(PageRepository::class),
            $this->createStub(IconFactory::class),
            $configurationService,
            $pageConfigurationResolver,
            $queueEntryWriter,
        );
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
            'URLs' => [],
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
