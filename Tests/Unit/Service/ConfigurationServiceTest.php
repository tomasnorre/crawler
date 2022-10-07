<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\UrlService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @covers \AOE\Crawler\Service\ConfigurationService
 * @covers \AOE\Crawler\Service\UrlService::compileUrls
 * @covers \AOE\Crawler\Configuration\ExtensionConfigurationProvider::getExtensionConfiguration
 */
class ConfigurationServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @dataProvider removeDisallowedConfigurationsDataProvider
     */
    public function removeDisallowedConfigurationsReturnsExpectedArray(array $allowed, array $configuration, array $expected): void
    {
        self::assertEquals(
            $expected,
            ConfigurationService::removeDisallowedConfigurations($allowed, $configuration)
        );
    }

    public function removeDisallowedConfigurationsDataProvider(): iterable
    {
        yield 'both allowed and configuration is empty' => [
            'allowed' => [],
            'configurations' => [],
            'expected' => [],
        ];
        yield 'allowed is empty' => [
            'allowed' => [],
            'configurations' => [
                'default' => 'configuration-text',
                'news' => 'configuration-text',
            ],
            'expected' => [
                'default' => 'configuration-text',
                'news' => 'configuration-text',
            ],
        ];
        yield 'news is not allowed' => [
            'allowed' => ['default'],
            'configurations' => [
                'default' => 'configuration-text',
                'news' => 'configuration-text',
            ],
            'expected' => ['default' => 'configuration-text'],
        ];
    }

    /**
     * @test
     * @dataProvider getConfigurationFromPageTSDataProvider
     */
    public function getConfigurationFromPageTS(array $pageTSConfig, int $pageId, string $mountPoint, array $compiledUrls, array $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];

        $urlService = $this->prophesize(UrlService::class);
        $urlService->compileUrls(Argument::any(), Argument::any(), Argument::any())->willReturn($compiledUrls);
        $configurationRepository = $this->prophesize(ConfigurationRepository::class);
        $configurationService = GeneralUtility::makeInstance(
            ConfigurationService::class,
            $urlService->reveal(),
            $configurationRepository->reveal()
        );

        self::assertEquals(
            $expected,
            $configurationService->getConfigurationFromPageTS($pageTSConfig, $pageId, [], $mountPoint)
        );
    }

    public function getConfigurationFromPageTSDataProvider(): iterable
    {
        yield 'Empty Array' => [
            'pageTSConfig' => [],
            'pageId' => 1,
            'mountPoint' => '',
            'compiledUrls' => [],
            'expected' => [],
        ];
        yield 'PageTSConfig with empty mountPoint returning no URLs' => [
            'pageTSConfig' => [
                'tx_crawler.' => [
                    'crawlerCfg.' => [
                        'paramSets.' => [
                            'myConfigurationKeyName' => '&tx_myext[items]=[_TABLE:tt_myext_items;_PID:15;_WHERE: hidden = 0]',
                            'myConfigurationKeyName.' => [
                                'pidsOnly' => '1',
                                'procInstrFilter' => 'tx_indexedsearch_reindex',
                            ],
                        ],
                    ],
                ],
            ],
            'pageId' => 1,
            'mountPoint' => '',
            'compiledUrls' => [],
            'expected' => [
                'myConfigurationKeyName' => [
                    'subCfg' => [
                        'pidsOnly' => '1',
                        'procInstrFilter' => 'tx_indexedsearch_reindex',
                        'key' => 'myConfigurationKeyName',
                    ],
                    'paramParsed' => [
                        'tx_myext[items]' => '[_TABLE:tt_myext_items;_PID:15;_WHERE: hidden = 0]',
                    ],
                    'paramExpanded' => [
                        'tx_myext[items]' => [],
                    ],
                    'origin' => 'pagets',
                    'URLs' => [],
                ],
            ],
        ];
        yield '_TABLE not at first position, returning the parts' => [
            'pageTSConfig' => [
                'tx_crawler.' => [
                    'crawlerCfg.' => [
                        'paramSets.' => [
                            'myConfigurationKeyName' => '&tx_myext[items]=[_FIELD:custom;_TABLE:tt_myext_items;_PID:15;_WHERE: hidden = 0]',
                            'myConfigurationKeyName.' => [
                                'pidsOnly' => '1',
                                'procInstrFilter' => 'tx_indexedsearch_reindex',
                            ],
                        ],
                    ],
                ],
            ],
            'pageId' => 1,
            'mountPoint' => '',
            'compiledUrls' => [],
            'expected' => [
                'myConfigurationKeyName' => [
                    'subCfg' => [
                        'pidsOnly' => '1',
                        'procInstrFilter' => 'tx_indexedsearch_reindex',
                        'key' => 'myConfigurationKeyName',
                    ],
                    'paramParsed' => [
                        'tx_myext[items]' => '[_FIELD:custom;_TABLE:tt_myext_items;_PID:15;_WHERE: hidden = 0]',
                    ],
                    'paramExpanded' => [
                        'tx_myext[items]' => ['_FIELD:custom;_TABLE:tt_myext_items;_PID:15;_WHERE: hidden = 0'],
                    ],
                    'origin' => 'pagets',
                    'URLs' => [],
                ],
            ],
        ];
        yield 'PageTSConfig with empty mountPoint returning URLs' => [
            'pageTSConfig' => [
                'tx_crawler.' => [
                    'crawlerCfg.' => [
                        'paramSets.' => [
                            'myConfigurationKeyName' => '&S=CRAWL&L=[0-1]',
                            'myConfigurationKeyName.' => [
                                'pidsOnly' => '1',
                                'procInstrFilter' => 'tx_indexedsearch_reindex',
                            ],
                        ],
                    ],
                ],
            ],
            'pageId' => 1,
            'mountPoint' => '',
            'compiledUrls' => [
                '?id=1&L=0&S=CRAWL',
                '?id=1&L=1&S=CRAWL',
            ],
            'expected' => [
                'myConfigurationKeyName' => [
                    'subCfg' => [
                        'pidsOnly' => '1',
                        'procInstrFilter' => 'tx_indexedsearch_reindex',
                        'key' => 'myConfigurationKeyName',
                    ],
                    'paramParsed' => [
                        'S' => 'CRAWL',
                        'L' => '[0-1]',
                    ],
                    'paramExpanded' => [
                        'S' => ['CRAWL'],
                        'L' => [0, 1],
                    ],
                    'origin' => 'pagets',
                    'URLs' => [
                        '?id=1&L=0&S=CRAWL',
                        '?id=1&L=1&S=CRAWL',
                    ],
                ],
            ],
        ];
        yield 'PageTSConfig with empty mountPoint returning URLs, switching range parameters around' => [
            'pageTSConfig' => [
                'tx_crawler.' => [
                    'crawlerCfg.' => [
                        'paramSets.' => [
                            'myConfigurationKeyName' => '&S=CRAWL&L=[0-1]&RANGE=[10-6]',
                            'myConfigurationKeyName.' => [
                                'pidsOnly' => '1',
                                'procInstrFilter' => 'tx_indexedsearch_reindex',
                            ],
                        ],
                    ],
                ],
            ],
            'pageId' => 1,
            'mountPoint' => '',
            'compiledUrls' => [
                '?id=1&L=0&S=CRAWL',
                '?id=1&L=1&S=CRAWL',
            ],
            'expected' => [
                'myConfigurationKeyName' => [
                    'subCfg' => [
                        'pidsOnly' => '1',
                        'procInstrFilter' => 'tx_indexedsearch_reindex',
                        'key' => 'myConfigurationKeyName',
                    ],
                    'paramParsed' => [
                        'S' => 'CRAWL',
                        'L' => '[0-1]',
                        'RANGE' => '[10-6]',
                    ],
                    'paramExpanded' => [
                        'S' => ['CRAWL'],
                        'L' => [0, 1],
                        'RANGE' => [6, 7, 8, 9, 10],
                    ],
                    'origin' => 'pagets',
                    'URLs' => [
                        '?id=1&L=0&S=CRAWL',
                        '?id=1&L=1&S=CRAWL',
                    ],
                ],
            ],
        ];
        yield 'PageTSConfig with mountPoint given' => [
            'pageTSConfig' => [
                'tx_crawler.' => [
                    'crawlerCfg.' => [
                        'paramSets.' => [
                            'myConfigurationKeyName' => '&S=CRAWL&L=[0-1]',
                            'myConfigurationKeyName.' => [
                                'pidsOnly' => '1',
                                'procInstrFilter' => 'tx_indexedsearch_reindex',
                            ],
                        ],
                    ],
                ],
            ],
            'pageId' => 1,
            'mountPoint' => 'mpstring',
            'compiledUrls' => [
                '?id=1&MP=mpstring&L=0&S=CRAWL',
                '?id=1&MP=mpstring&L=1&S=CRAWL',
            ],
            'expected' => [
                'myConfigurationKeyName' => [
                    'subCfg' => [
                        'pidsOnly' => '1',
                        'procInstrFilter' => 'tx_indexedsearch_reindex',
                        'key' => 'myConfigurationKeyName',
                    ],
                    'paramParsed' => [
                        'S' => 'CRAWL',
                        'L' => '[0-1]',
                    ],
                    'paramExpanded' => [
                        'S' => ['CRAWL'],
                        'L' => [0, 1],
                    ],
                    'origin' => 'pagets',
                    'URLs' => [
                        '?id=1&MP=mpstring&L=0&S=CRAWL',
                        '?id=1&MP=mpstring&L=1&S=CRAWL',
                    ],
                ],
            ],
        ];

        yield 'PageTSConfig with mountPoint given, but with params that needs trimming' => [
            'pageTSConfig' => [
                'tx_crawler.' => [
                    'crawlerCfg.' => [
                        'paramSets.' => [
                            'myConfigurationKeyName' => '&S=CRAWL&L=[0-1]',
                            'myConfigurationKeyName.' => [
                                'pidsOnly' => '1',
                                'procInstrFilter' => ' tx_indexedsearch_reindex ',
                            ],
                        ],
                    ],
                ],
            ],
            'pageId' => 1,
            'mountPoint' => 'mpstring',
            'compiledUrls' => [
                '?id=1&MP=mpstring&L=0&S=CRAWL',
                '?id=1&MP=mpstring&L=1&S=CRAWL',
            ],
            'expected' => [
                'myConfigurationKeyName' => [
                    'subCfg' => [
                        'pidsOnly' => '1',
                        'procInstrFilter' => 'tx_indexedsearch_reindex',
                        'key' => 'myConfigurationKeyName',
                    ],
                    'paramParsed' => [
                        'S' => 'CRAWL',
                        'L' => '[0-1]',
                    ],
                    'paramExpanded' => [
                        'S' => ['CRAWL'],
                        'L' => [0, 1],
                    ],
                    'origin' => 'pagets',
                    'URLs' => [
                        '?id=1&MP=mpstring&L=0&S=CRAWL',
                        '?id=1&MP=mpstring&L=1&S=CRAWL',
                    ],
                ],
            ],
        ];
    }
}
