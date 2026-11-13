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
use AOE\Crawler\Service\ExcludeStringExpander;
use AOE\Crawler\Service\ParameterExpander;
use AOE\Crawler\Service\UrlService;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Service\ConfigurationService::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Service\ParameterExpander::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Service\UrlService::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Configuration\ExtensionConfigurationProvider::class)]
class ConfigurationServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    #[\PHPUnit\Framework\Attributes\DataProvider('removeDisallowedConfigurationsDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function removeDisallowedConfigurationsReturnsExpectedArray(
        array $allowed,
        array $configuration,
        array $expected
    ): void {
        self::assertEquals(
            $expected,
            ConfigurationService::removeDisallowedConfigurations($allowed, $configuration)
        );
    }

    public static function removeDisallowedConfigurationsDataProvider(): iterable
    {
        yield 'both allowed and configuration is empty' => [
            'allowed' => [],
            'configuration' => [],
            'expected' => [],
        ];
        yield 'allowed is empty' => [
            'allowed' => [],
            'configuration' => [
                'default' => 'configuration-text',
                'news' => 'configuration-text',
            ],
            'expected' => [
                'default' => 'configuration-text',
                'news' => 'configuration-text',
            ],
        ];
        yield 'all configurations are allowed' => [
            'allowed' => ['default', 'news'],
            'configuration' => [
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
            'configuration' => [
                'default' => 'configuration-text',
                'news' => 'configuration-text',
            ],
            'expected' => [
                'default' => 'configuration-text',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getConfigurationFromPageTSDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationFromPageTS(
        array $pageTSConfig,
        int $pageId,
        string $mountPoint,
        array $compiledUrls,
        array $expected
    ): void {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];

        $urlService = $this->prophesize(UrlService::class);
        $urlService->compileUrls(Argument::any(), Argument::any(), Argument::any())->willReturn($compiledUrls);
        $configurationRepository = $this->prophesize(ConfigurationRepository::class);
        $configurationService = GeneralUtility::makeInstance(
            ConfigurationService::class,
            $urlService->reveal(),
            $configurationRepository->reveal(),
            $this->createStub(GroupResolver::class),
            new ParameterExpander(),
            new ExcludeStringExpander(),
        );

        self::assertEquals(
            $expected,
            $configurationService->getConfigurationFromPageTS($pageTSConfig, $pageId, [], $mountPoint)
        );
    }

    public static function getConfigurationFromPageTSDataProvider(): iterable
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
            'compiledUrls' => ['?id=1&L=0&S=CRAWL', '?id=1&L=1&S=CRAWL'],
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
                    'URLs' => ['?id=1&L=0&S=CRAWL', '?id=1&L=1&S=CRAWL'],
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
            'compiledUrls' => ['?id=1&L=0&S=CRAWL', '?id=1&L=1&S=CRAWL'],
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
                    'URLs' => ['?id=1&L=0&S=CRAWL', '?id=1&L=1&S=CRAWL'],
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
            'compiledUrls' => ['?id=1&MP=mpstring&L=0&S=CRAWL', '?id=1&MP=mpstring&L=1&S=CRAWL'],
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
                    'URLs' => ['?id=1&MP=mpstring&L=0&S=CRAWL', '?id=1&MP=mpstring&L=1&S=CRAWL'],
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
            'compiledUrls' => ['?id=1&MP=mpstring&L=0&S=CRAWL', '?id=1&MP=mpstring&L=1&S=CRAWL'],
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
                    'URLs' => ['?id=1&MP=mpstring&L=0&S=CRAWL', '?id=1&MP=mpstring&L=1&S=CRAWL'],
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('compileUrlsArgumentsDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationFromPageTSCompilesUrlsOfPage(
        array $extensionSettings,
        string $mountPoint,
        string $expectedUrl,
        int $expectedMaxUrls
    ): void {
        $urlService = $this->createMock(UrlService::class);
        $urlService->expects(self::once())->method('compileUrls')
            ->with([
                'L' => ['1'],
            ], [$expectedUrl], $expectedMaxUrls)
            ->willReturn(['compiled']);

        $result = $this->getConfigurationService($urlService, $extensionSettings)->getConfigurationFromPageTS(
            $this->getPageTsConfig([
                'default' => '&L=1',
            ]),
            5,
            [],
            $mountPoint
        );

        self::assertSame(['compiled'], $result['default']['URLs']);
    }

    public static function compileUrlsArgumentsDataProvider(): iterable
    {
        yield 'Default maximum of URLs' => [[], '', '?id=5', 10_000];
        yield 'Mount point' => [[], '3-4', '?id=5&MP=3-4', 10_000];
        yield 'Configured maximum of URLs' => [[
            'maxCompileUrls' => '5',
        ], '', '?id=5', 5];
        yield 'Lowest maximum of URLs' => [[
            'maxCompileUrls' => '1',
        ], '', '?id=5', 1];
        yield 'No maximum of URLs' => [[
            'maxCompileUrls' => '0',
        ], '', '?id=5', 10_000];
        yield 'Maximum of URLs below range' => [[
            'maxCompileUrls' => '-5',
        ], '', '?id=5', 1];
        yield 'Highest maximum of URLs' => [[
            'maxCompileUrls' => '1000000000',
        ], '', '?id=5', 1_000_000_000];
        yield 'Maximum of URLs above range' => [[
            'maxCompileUrls' => '1000000001',
        ], '', '?id=5', 1_000_000_000];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pidsOnlyDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationFromPageTSRespectsPidsOnly(string $pidsOnly, bool $expectedToBeProcessed): void
    {
        $pageTsConfig = $this->getPageTsConfig([
            'default' => '&L=1',
        ], [
            'default' => [
                'pidsOnly' => $pidsOnly,
            ],
        ]);

        $result = $this->getConfigurationService()->getConfigurationFromPageTS($pageTsConfig, 5, []);

        self::assertSame($expectedToBeProcessed, isset($result['default']));
    }

    public static function pidsOnlyDataProvider(): iterable
    {
        yield 'No pidsOnly' => ['', true];
        yield 'Page is listed' => ['5', true];
        yield 'Page is listed with spaces' => [' 3 , 5 ', true];
        yield 'Page is not listed' => ['3,4', false];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationFromPageTSIgnoresParamSetsWithoutConfiguration(): void
    {
        $pageTsConfig = [
            'tx_crawler.' => [
                'crawlerCfg.' => [
                    'paramSets.' => [
                        'default' => '&L=1',
                    ],
                ],
            ],
        ];

        self::assertSame([], $this->getConfigurationService()->getConfigurationFromPageTS($pageTsConfig, 5, []));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationFromPageTSReturnsAllParamSets(): void
    {
        $result = $this->getConfigurationService()->getConfigurationFromPageTS(
            $this->getPageTsConfig([
                'default' => '&L=1',
                'news' => '&L=2',
            ]),
            5,
            []
        );

        self::assertSame(['default', 'news'], array_keys($result));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('expandParametersDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationFromPageTSExpandsParameters(string $paramSet, array $expected): void
    {
        $result = $this->getConfigurationService()->getConfigurationFromPageTS(
            $this->getPageTsConfig([
                'default' => $paramSet,
            ]),
            5,
            []
        );

        self::assertSame($expected, $result['default']['paramExpanded']);
    }

    public static function expandParametersDataProvider(): iterable
    {
        yield 'Literal value' => [
            '&L=1', [
                'L' => ['1'],
            ]];
        yield 'Values wrapped in spaces' => [
            '&L= [a|b] ', [
                'L' => ['a', 'b'],
            ]];
        yield 'Missing closing bracket' => [
            '&L=[a|b', [
                'L' => ['[a|b'],
            ]];
        yield 'Missing opening bracket' => [
            '&L=a|b]', [
                'L' => ['a|b]'],
            ]];
        // The first value is the matched string, the following are incremented integers
        yield 'Range' => [
            '&L=[1 - 3]', [
                'L' => ['1', 2, 3],
            ]];
        yield 'Range with spaces' => [
            '&L=[ 1-2 |x]', [
                'L' => ['1', 2, 'x'],
            ]];
        yield 'Text before range' => [
            '&L=[a1-3]', [
                'L' => ['a1-3'],
            ]];
        yield 'Text after range' => [
            '&L=[1-3a]', [
                'L' => ['1-3a'],
            ]];
        yield 'Duplicate values' => [
            '&L=[a|a|b]', [
                'L' => [
                    0 => 'a',
                    2 => 'b',
                ],
            ]];
        yield 'Table with spaces and unknown table' => [
            '&L=[ _TABLE:tx_unknown_table]', [
                'L' => [],
            ]];
        yield 'Parameters are sorted' => [
            '&b=[1]&a=[2]', [
                'a' => ['2'],
                'b' => ['1'],
            ]];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationFromPageTSLimitsRangesToThousandValues(): void
    {
        $result = $this->getConfigurationService()->getConfigurationFromPageTS(
            $this->getPageTsConfig([
                'default' => '&L=[1-1005]',
            ]),
            5,
            []
        );

        self::assertSame(['1', ...range(2, 1000)], $result['default']['paramExpanded']['L']);
    }

    private function getConfigurationService(
        ?UrlService $urlService = null,
        array $extensionSettings = []
    ): ConfigurationService {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $extensionSettings;
        if ($urlService === null) {
            $urlService = $this->createStub(UrlService::class);
            $urlService->method('compileUrls')->willReturn([]);
        }

        return new ConfigurationService(
            $urlService,
            $this->createStub(ConfigurationRepository::class),
            $this->createStub(GroupResolver::class),
            new ParameterExpander(),
            new ExcludeStringExpander(),
        );
    }

    /**
     * @param array<string, string> $paramSets
     * @param array<string, array<string, string>> $subConfigurations
     */
    private function getPageTsConfig(array $paramSets, array $subConfigurations = []): array
    {
        $configuration = [];
        foreach ($paramSets as $key => $paramSet) {
            $configuration[$key] = $paramSet;
            $configuration[$key . '.'] = $subConfigurations[$key] ?? [];
        }

        return [
            'tx_crawler.' => [
                'crawlerCfg.' => [
                    'paramSets.' => $configuration,
                ],
            ],
        ];
    }
}
