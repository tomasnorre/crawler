<?php

declare(strict_types=1);

namespace TomasNorre\Crawler\Tests\Unit\Service;

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

use Nimut\TestingFramework\TestCase\UnitTestCase;
use TomasNorre\Crawler\Service\ConfigurationService;
use TomasNorre\Crawler\Service\UrlService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationServiceTest extends UnitTestCase
{
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

    public function removeDisallowedConfigurationsDataProvider(): array
    {
        return [
            'both allowed and configuration is empty' => [
                'allowed' => [],
                'configurations' => [],
                'expected' => [],
            ],
            'allowed is empty' => [
                'allowed' => [],
                'configurations' => [
                    'default' => 'configuration-text',
                    'news' => 'configuration-text',
                ],
                'expected' => [
                    'default' => 'configuration-text',
                    'news' => 'configuration-text',
                ],
            ],
            'news is not allowed' => [
                'allowed' => ['default'],
                'configurations' => [
                    'default' => 'configuration-text',
                    'news' => 'configuration-text',
                ],
                'expected' => ['default' => 'configuration-text'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getConfigurationFromPageTSDataProvider
     */
    public function getConfigurationFromPageTS(array $pageTSConfig, int $pageId, string $mountPoint, array $expected): void
    {
        $urlService = GeneralUtility::makeInstance(UrlService::class);
        $serviceConfiguration = $this->createPartialMock(ConfigurationService::class, ['getUrlService']);
        $serviceConfiguration->expects($this->any())->method('getUrlService')->willReturn($urlService);

        self::assertEquals(
            $expected,
            $serviceConfiguration->getConfigurationFromPageTS($pageTSConfig, $pageId, [], $mountPoint)
        );
    }

    public function getConfigurationFromPageTSDataProvider(): array
    {
        return [
            'Empty Array' => [
                'pageTSConfig' => [],
                'pageId' => 1,
                'mountPoint' => '',
                'expected' => [],
            ],
            'PageTSConfig with mountPoint false' => [
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
            ],
            'PageTSConfig with mountPoint string' => [
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
            ],
        ];
    }
}
