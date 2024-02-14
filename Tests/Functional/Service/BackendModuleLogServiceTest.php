<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

use AOE\Crawler\Service\BackendModuleLogService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(BackendModuleLogService::class)]
class BackendModuleLogServiceTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    #[DataProvider('addRowsDataProvider')]
    #[Test]
    public function addRows(
        string $title,
        int $setId,
        string $showResultLog,
        string $showFeVars,
        bool $CSVExport,
        array $logEntries,
        array $expected
    ): void {
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $subject = GeneralUtility::makeInstance(BackendModuleLogService::class);

        [$result, $CSVData] = $subject->addRows(
            $logEntries,
            $setId,
            $title,
            $showResultLog,
            $showFeVars,
            $CSVExport
        );

        // To easy the work with the result data, as i multidimensional array.
        $resultArray = $result[0];
        $expectedArray = $expected[0][0];
        $csvDataArray = $expected[1];

        $propertiesToCheck = ['title', 'colSpan', 'titleRowSpan', 'trClass'];
        foreach ($propertiesToCheck as $property) {
            $this->assertEquals($resultArray[$property], $expectedArray[$property]);
        }

        $this->assertEquals($resultArray['qid']['link_text'], $logEntries['qid']);
        $this->assertArrayHasKey('link', $resultArray['qid']);
        $this->assertArrayHasKey('link', $resultArray['refresh']);
        $this->assertArrayHasKey('link-text', $resultArray['refresh']);
        $this->assertArrayHasKey('warning', $resultArray['refresh']);
        $this->assertEquals($expectedArray['columns'], $resultArray['columns']);
        $this->assertEquals($csvDataArray, $CSVData);
    }

    #[DataProvider('addRowsNoEntriesDataProvider')]
    #[Test]
    public function addRowsNoEntries(
        string $title,
        int $setId,
        string $showResultLog,
        string $showFeVars,
        bool $CSVExport,
        array $logEntries,
        array $expected
    ): void {
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $subject = GeneralUtility::makeInstance(BackendModuleLogService::class);

        [$result, $CSVData] = $subject->addRows(
            $logEntries,
            $setId,
            $title,
            $showResultLog,
            $showFeVars,
            $CSVExport
        );

        // To easy the work with the result data, as i multidimensional array.
        $resultArray = $result[0];
        $expectedArray = $expected[0][0];

        $propertiesToCheck = ['title', 'colSpan', 'titleRowSpan', 'trClass'];
        foreach ($propertiesToCheck as $property) {
            $this->assertEquals($resultArray[$property], $expectedArray[$property]);
        }
        $this->assertEmpty($CSVData);
    }

    public static function addRowsNoEntriesDataProvider(): \Iterator
    {
        $title = 'Testing';
        $setId = 987654;

        yield 'No log entries' => [
            'title' => $title,
            'setId' => $setId,
            'showResultLog' => '1',
            'showFeVars' => '1',
            'CVSExport' => false,
            'logEntries' => [],
            'expected' => [[[
                'titleRowSpan' => 1,
                'colSpan' => 11,
                'title' => $title,
                'noEntries' => '',
            ]], []],
        ];
    }

    public static function addRowsDataProvider(): \Iterator
    {
        $title = 'Testing';
        $setId = 987654;
        $scheduled = 1_674_825_269;

        yield 'One log entry' => [
            'title' => $title,
            'setId' => $setId,
            'showResultLog' => '1',
            'showFeVars' => '1',
            'CVSExport' => false,
            'logEntries' => [
                [
                    'qid' => 20,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => $scheduled,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ],
            ],
            'expected' => [[[
                'titleRowSpan' => 1,
                'colSpan' => 11,
                'title' => $title,
                'trClass' => '',
                'columns' => [
                    'result_log' => '',
                    'result_status' => '-',
                    'url' => '<a href="/" target="_newWIndow">/</a>',
                    'feUserGroupList' => '',
                    'procInstructions' => '',
                    'set_id' => '987654',
                    'tsfe_id' => '',
                    'tsfe_gr_list' => '',
                ],
            ]], []],
        ];

        yield 'two log entries' => [
            'title' => $title,
            'setId' => $setId,
            'showResultLog' => '1',
            'showFeVars' => '1',
            'CVSExport' => false,
            'logEntries' => [
                [
                    'qid' => 20,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => $scheduled,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ],
                [
                    'qid' => 21,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => $scheduled,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ],
            ],
            'expected' => [[[
                'titleRowSpan' => 2,
                'colSpan' => 11,
                'title' => $title,
                'trClass' => '',
                'columns' => [
                    'result_log' => '',
                    'result_status' => '-',
                    'url' => '<a href="/" target="_newWIndow">/</a>',
                    'feUserGroupList' => '',
                    'procInstructions' => '',
                    'set_id' => '987654',
                    'tsfe_id' => '',
                    'tsfe_gr_list' => '',
                ],
            ]], []],
        ];

        yield 'One log entry, show resultLog and showFeVars, scheduled' => [
            'title' => $title,
            'setId' => $setId,
            'showResultLog' => '0',
            'showFeVars' => '0',
            'CVSExport' => false,
            'logEntries' => [
                [
                    'qid' => 20,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => $scheduled,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ],
            ],
            'expected' => [[[
                'titleRowSpan' => 1,
                'colSpan' => 9,
                'title' => $title,
                'trClass' => '',
                'columns' => [
                    'result_status' => '-',
                    'url' => '<a href="/" target="_newWIndow">/</a>',
                    'feUserGroupList' => '',
                    'procInstructions' => '',
                    'set_id' => '987654',
                    'scheduled' => date('d-m-y H:i', $scheduled),
                    'exec_time' => '-',
                ],
            ]], []],
        ];

        yield 'One log entry, show resultLog and showFeVars, not scheduled' => [
            'title' => $title,
            'setId' => $setId,
            'showResultLog' => '0',
            'showFeVars' => '0',
            'CVSExport' => false,
            'logEntries' => [
                [
                    'qid' => 20,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => 0,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ],
            ],
            'expected' => [[[
                'titleRowSpan' => 1,
                'colSpan' => 9,
                'title' => $title,
                'trClass' => '',
                'columns' => [
                    'result_status' => '-',
                    'url' => '<a href="/" target="_newWIndow">/</a>',
                    'feUserGroupList' => '',
                    'procInstructions' => '',
                    'set_id' => '987654',
                    'scheduled' => '-',
                    'exec_time' => '-',
                ],
            ]], []],
        ];

        yield 'One log entry, Error in result_data' => [
            'title' => $title,
            'setId' => $setId,
            'showResultLog' => '0',
            'showFeVars' => '0',
            'CVSExport' => false,
            'logEntries' => [
                [
                    'qid' => 20,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => 0,
                    'set_id' => $setId,
                    'result_data' => json_encode(['content' => 'sdafds']),
                    'exec_time' => 0,
                ],
            ],
            'expected' => [[[
                'titleRowSpan' => 1,
                'colSpan' => 9,
                'title' => $title,
                'trClass' => 'bg-danger',
                'columns' => [
                    'result_status' => 'Error - no info, sorry!',
                    'url' => '<a href="/" target="_newWIndow">/</a>',
                    'feUserGroupList' => '',
                    'procInstructions' => '',
                    'set_id' => '987654',
                    'scheduled' => '-',
                    'exec_time' => '-',
                ],
            ]], []],
        ];

        yield 'One log entry, CVSExport is true' => [
            'title' => $title,
            'setId' => $setId,
            'showResultLog' => '0',
            'showFeVars' => '0',
            'CVSExport' => true,
            'logEntries' => [
                [
                    'qid' => 20,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => 0,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ],
            ],
            'expected' => [
                [
                    [
                        'titleRowSpan' => 1,
                        'colSpan' => 9,
                        'title' => $title,
                        'trClass' => '',
                        'columns' => [
                            'result_status' => '-',
                            'url' => '<a href="/" target="_newWIndow">/</a>',
                            'feUserGroupList' => '',
                            'procInstructions' => '',
                            'set_id' => '987654',
                            'scheduled' => '-',
                            'exec_time' => '-',
                        ],
                    ],
                ],
                [
                    'scheduled' => '01-01-70 00:00',
                    'exec_time' => '-',
                    'result_status' => '-',
                    'url' => '<a href="/" target="_newWIndow">/</a>',
                    'feUserGroupList' => '',
                    'procInstructions' => '',
                    'set_id' => '987654',
                    'result_log' => '',
                    'qid' => 20,
                ],
            ],
        ];
    }
}
