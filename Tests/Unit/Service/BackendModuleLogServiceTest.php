<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

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

use AOE\Crawler\Service\BackendModuleLogService;
use AOE\Crawler\Value\CrawlResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(BackendModuleLogService::class)]
class BackendModuleLogServiceTest extends UnitTestCase
{
    private BackendModuleLogService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new BackendModuleLogService($this->createStub(IconFactory::class));
    }

    public static function contentArrayDataProvider(): iterable
    {
        yield 'neither result log nor frontend variables' => ['', '', 9];
        yield 'result log replaces scheduled and execution time' => ['1', '', 8];
        yield 'frontend variables add three columns' => ['', '1', 12];
        yield 'result log and frontend variables' => ['1', '1', 11];
    }

    #[DataProvider('contentArrayDataProvider')]
    #[Test]
    public function configureContentArraySetsColumnSpan(
        string $showResultLog,
        string $showFeVars,
        int $expectedColSpan
    ): void {
        self::assertSame([
            'titleRowSpan' => 1,
            'colSpan' => $expectedColSpan,
        ], $this->subject->configureContentArray($showResultLog, $showFeVars));
    }

    #[Test]
    public function buildRowDataShowsResultLog(): void
    {
        $result = CrawlResult::fromContent([
            'log' => ['User Groups: 1', 'Second line'],
        ]);

        self::assertSame([
            'result_log' => 'User Groups: 1' . chr(10) . 'Second line',
            'result_status' => 'OK',
            'url' => 'https://example.com/?a=1&amp;b=2',
            'feUserGroupList' => '1,2',
            'procInstructions' => 'tx_indexedsearch_reindex; tx_other',
            'set_id' => '34',
        ], $this->buildRowData('1', 'OK', $result, [
            'url' => 'https://example.com/?a=1&b=2',
            'feUserGroupList' => '1,2',
            'procInstructions' => ['tx_indexedsearch_reindex', 'tx_other'],
        ]));
    }

    #[Test]
    public function buildRowDataShowsEmptyResultLogOfEntryNotCrawled(): void
    {
        self::assertSame('', $this->buildRowData('1', '-', null)['result_log']);
    }

    #[Test]
    public function buildRowDataShowsTimesWithoutResultLog(): void
    {
        $rowData = $this->buildRowData('', '-', null);

        self::assertArrayNotHasKey('result_log', $rowData);
        self::assertSame('-', $rowData['scheduled']);
        self::assertSame('-', $rowData['exec_time']);
    }

    #[Test]
    public function buildRowDataShortensStatus(): void
    {
        self::assertSame(
            str_repeat('a', 50) . '...',
            $this->buildRowData('1', str_repeat('a', 60), null)['result_status']
        );
    }

    public static function parametersDataProvider(): iterable
    {
        yield 'no parameters' => [
            'parameters' => [],
            'expected' => [
                'url' => '',
                'feUserGroupList' => '',
                'procInstructions' => '',
            ],
        ];
        yield 'URL before alternative URL' => [
            'parameters' => [
                'url' => '/url',
                'alturl' => '/alternative',
            ],
            'expected' => [
                'url' => '/url',
                'feUserGroupList' => '',
                'procInstructions' => '',
            ],
        ];
        yield 'quotes of URL are escaped for HTML5' => [
            'parameters' => [
                'url' => '/?a="b"&c=\'d\'',
            ],
            'expected' => [
                'url' => '/?a=&quot;b&quot;&amp;c=&apos;d&apos;',
                'feUserGroupList' => '',
                'procInstructions' => '',
            ],
        ];
        yield 'alternative URL' => [
            'parameters' => [
                'alturl' => '/alternative',
            ],
            'expected' => [
                'url' => '/alternative',
                'feUserGroupList' => '',
                'procInstructions' => '',
            ],
        ];
        yield 'processing instructions are no array' => [
            'parameters' => [
                'url' => '/',
                'procInstructions' => 'tx_indexedsearch_reindex',
            ],
            'expected' => [
                'url' => '/',
                'feUserGroupList' => '',
                'procInstructions' => '',
            ],
        ];
    }

    #[DataProvider('parametersDataProvider')]
    #[Test]
    public function buildRowDataReadsParameters(array $parameters, array $expected): void
    {
        $rowData = $this->buildRowData('1', 'OK', null, $parameters);

        self::assertSame($expected, [
            'url' => $rowData['url'],
            'feUserGroupList' => $rowData['feUserGroupList'],
            'procInstructions' => $rowData['procInstructions'],
        ]);
    }

    public static function feVarsDataProvider(): iterable
    {
        yield 'frontend variables of the result' => [
            'result' => CrawlResult::fromContent([
                'vars' => [
                    'id' => 5,
                    'gr_list' => '0,-2,1',
                ],
            ]),
            'expected' => [
                'tsfe_id' => 5,
                'tsfe_gr_list' => '0,-2,1',
            ],
        ];
        yield 'result without frontend variables' => [
            'result' => CrawlResult::fromContent([]),
            'expected' => [
                'tsfe_id' => '',
                'tsfe_gr_list' => '',
            ],
        ];
        yield 'entry not crawled' => [
            'result' => null,
            'expected' => [
                'tsfe_id' => '',
                'tsfe_gr_list' => '',
            ],
        ];
    }

    #[DataProvider('feVarsDataProvider')]
    #[Test]
    public function populateFeVarsAddsFrontendVariables(?CrawlResult $result, array $expected): void
    {
        self::assertSame(
            [
                'url' => '/',
            ] + $expected,
            (new \ReflectionMethod($this->subject, 'populateFeVars'))->invoke($this->subject, '1', $result, [
                'url' => '/',
            ])
        );
    }

    #[Test]
    public function populateFeVarsAddsNothingIfFrontendVariablesAreNotShown(): void
    {
        $result = CrawlResult::fromContent([
            'vars' => [
                'id' => 5,
            ],
        ]);

        self::assertSame(
            [
                'url' => '/',
            ],
            (new \ReflectionMethod($this->subject, 'populateFeVars'))->invoke($this->subject, '', $result, [
                'url' => '/',
            ])
        );
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function buildRowData(
        string $showResultLog,
        string $resStatus,
        ?CrawlResult $result,
        array $parameters = []
    ): array {
        return (new \ReflectionMethod($this->subject, 'buildRowData'))->invoke($this->subject, [
            'parameters' => json_encode($parameters),
            'scheduled' => 0,
            'exec_time' => 0,
            'set_id' => 34,
        ], $showResultLog, $resStatus, $result);
    }
}
