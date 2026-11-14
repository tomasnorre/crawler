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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use AOE\Crawler\Value\QueueRow;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
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

        $crawlerController = $this->createCrawlerController(
            ['urlListFromUrlArray'],
            $pageConfigurationResolver,
            $configurationService
        );
        $crawlerController->method('urlListFromUrlArray')->willReturnCallback(
            static fn (array $configuration): string => 'URLs of ' . $configuration['subCfg']['key']
        );

        return $crawlerController;
    }

    /**
     * @param list<non-empty-string> $mockedMethods
     */
    private function createCrawlerController(
        array $mockedMethods,
        PageConfigurationResolver $pageConfigurationResolver,
        ConfigurationService $configurationService
    ): CrawlerController&MockObject {
        return $this->getMockBuilder(CrawlerController::class)
            ->setConstructorArgs([
                $this->createStub(QueueRepository::class),
                $this->createStub(PageRepository::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(IconFactory::class),
                $configurationService,
                $pageConfigurationResolver,
                $this->createStub(UrlService::class),
                new ProcessInstructionService(),
                new CrawlUrlMessageDispatcher($this->createStub(MessageBusInterface::class)),
                $this->createConfiguredStub(ExtensionConfigurationProvider::class, [
                    'getExtensionConfiguration' => [],
                ]),
            ])
            ->onlyMethods($mockedMethods)
            ->getMock();
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
