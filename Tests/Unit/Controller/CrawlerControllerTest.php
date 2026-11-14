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
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageService;
use AOE\Crawler\Value\QueueRow;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @package AOE\Crawler\Tests
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Controller\CrawlerController::class)]
class CrawlerControllerTest extends UnitTestCase
{
    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    protected bool $resetSingletonInstances = true;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        $this->crawlerController = $this->createPartialMock(CrawlerController::class, []);

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

    /**
     * Resets the test environment after the test.
     */
    protected function tearDown(): void
    {
        unset($this->crawlerController);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getUrlsForPageRowSetsSkipMessageIfUidNotAnInteger(): void
    {
        $skipMessage = '';
        $this->crawlerController->getUrlsForPageRow([
            'uid' => 'string',
        ], $skipMessage);
        self::assertEquals('PageUid "string" was not an integer', $skipMessage);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getUrlsForPageRowDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getUrlsForPageRow(
        bool $checkIfPageSkipped,
        array $getUrlsForPages,
        array $pageRow,
        string $skipMessage,
        array $expected
    ): void {
        $mockedPageService = $this->createPartialMock(PageService::class, ['checkIfPageShouldBeSkipped']);
        if ($checkIfPageSkipped) {
            $mockedPageService->method('checkIfPageShouldBeSkipped')->willReturn($skipMessage);
        } else {
            $mockedPageService->method('checkIfPageShouldBeSkipped')->willReturn($checkIfPageSkipped);
        }

        /** @var MockObject|CrawlerController $crawlerController */
        $crawlerController = $this->createPartialMock(CrawlerController::class, ['getPageService', 'getUrlsForPageId']);
        $crawlerController->method('getPageService')->willReturn($mockedPageService);
        $crawlerController->method('getUrlsForPageId')->willReturn($getUrlsForPages);

        self::assertEquals($expected, $crawlerController->getUrlsForPageRow($pageRow, $skipMessage));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationHash(): void
    {
        $crawlerController = $this->getAccessibleMock(CrawlerController::class, null, [], '', false);

        $configuration = [
            'paramExpanded' => 'extendedParameter',
            'URLs' => 'URLs',
            'NotImportantParameter' => 'value not important',
        ];

        $originalCheckSum = md5(serialize($configuration));

        self::assertNotEquals(
            $originalCheckSum,
            $crawlerController->_call('getConfigurationHash', $configuration)
        );

        unset($configuration['paramExpanded'], $configuration['URLs']);
        $newCheckSum = md5(serialize($configuration));
        self::assertEquals($newCheckSum, $crawlerController->_call('getConfigurationHash', $configuration));
    }

    public static function getUrlsForPageRowDataProvider(): iterable
    {
        yield 'Message equals false, returns Urls from getUrlsForPages()' => [
            'checkIfPageSkipped' => false,
            'getUrlsForPages' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
            'pageRow' => [
                'uid' => 2001,
            ],
            'skipMessage' => 'Just variable placeholder, not used in tests as parsed as reference',
            'expected' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
        ];
        yield 'Message string not empty, returns empty array' => [
            'checkIfPageSkipped' => true,
            'getUrlsForPages' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
            'pageRow' => [
                'uid' => 2001,
            ],
            'skipMessage' => 'Just variable placeholder, not used in tests as parsed as reference',
            'expected' => [],
        ];
        yield 'PageRow Uid is string with int value' => [
            'checkIfPageSkipped' => false,
            'getUrlsForPages' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
            'pageRow' => [
                'uid' => '2001',
            ],
            'skipMessage' => 'Just variable placeholder, not used in tests as parsed as reference',
            'expected' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
        ];
        yield 'PageRow Uid is string with string value' => [
            'checkIfPageSkipped' => true,
            'getUrlsForPages' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
            'pageRow' => [
                'uid' => 'string',
            ],
            'skipMessage' => 'PageUid "string" was not an integer',
            'expected' => [],
        ];
        yield 'PageRow-array does not contain uid' => [
            'checkIfPageSkipped' => true,
            'getUrlsForPages' => ['index.php?q=search&page=1', 'index.php?q=search&page=2'],
            'pageRow' => [],
            'skipMessage' => 'pagRow[\'uid\'] is missing',
            'expected' => [],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getConfigurationHasReturnsExpectedValueDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationHasReturnsExpectedValue(array $configuration, string $expected): void
    {
        $crawlerLib = $this->getAccessibleMock(CrawlerController::class, null, [], '', false);

        self::assertEquals($expected, $crawlerLib->_call('getConfigurationHash', $configuration));
    }

    public static function getConfigurationHasReturnsExpectedValueDataProvider(): iterable
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
    ): CrawlerController&MockObject {
        $pageService = $this->createPartialMock(PageService::class, ['checkIfPageShouldBeSkipped']);
        $pageService->method('checkIfPageShouldBeSkipped')->willReturn($skipMessage);

        $configurationService = $this->createMock(ConfigurationService::class);
        $configurationService->method('expandExcludeString')->willReturnCallback(
            static fn (string $exclude): array => array_map(
                intval(...),
                GeneralUtility::intExplode(',', $exclude, true)
            )
        );

        $crawlerController = $this->createPartialMock(
            CrawlerController::class,
            ['getPageService', 'getUrlsForPageId', 'urlListFromUrlArray']
        );
        $crawlerController->method('getPageService')->willReturn($pageService);
        $crawlerController->method('getUrlsForPageId')->willReturn($configurations);
        $crawlerController->method('urlListFromUrlArray')->willReturnCallback(
            static fn (array $configuration): string => 'URLs of ' . $configuration['subCfg']['key']
        );
        (new \ReflectionProperty(CrawlerController::class, 'configurationService'))
            ->setValue($crawlerController, $configurationService);

        return $crawlerController;
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
