<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

use AOE\Crawler\Service\BackendModuleLogService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @covers \AOE\Crawler\Service\BackendModuleLogService
 */
class BackendModuleLogServiceTest extends FunctionalTestCase
{
    private BackendModuleLogService $subject;

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @test
     * @dataProvider addRowsDataProvider
     */
    public function addRows(string $title, int $setId, array $logEntries, array $expected): void
    {
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $subject = GeneralUtility::makeInstance(BackendModuleLogService::class);

        $result = $subject->addRows($logEntries, $setId, $title, 'false', 'false', false);

        // To easy the work with the result data, as i multidimensional array.
        $resultArray = $result[0][0];
        $expectedArray = $expected[0][0];

        $propertiesToCheck = ['title', 'colSpan', 'titleRowSpan','trClass'];
        foreach ($propertiesToCheck as $property ){
            $this->assertEquals($resultArray[$property], $expectedArray[$property]);
        }

        if (!empty($logEntries)) {
            $this->assertEquals($resultArray['qid']['link_text'], $logEntries['qid']);
            $this->assertArrayHasKey('link', $resultArray['qid']);
            $this->assertArrayHasKey('link', $resultArray['refresh']);
            $this->assertArrayHasKey('link-text', $resultArray['refresh']);
            $this->assertArrayHasKey('warning', $resultArray['refresh']);

            $this->assertEquals(
                $expectedArray['columns'],
                $resultArray['columns']
            );
        }



    }

    public function addRowsDataProvider(): \Iterator
    {
        $title = 'Testing';
        $setId = 987654;

        yield 'No log entries' => [
            'title' => $title,
            'setId' => $setId,
            'logEntries' => [],
            'expected' => [[[
                'titleRowSpan' => 1,
                'colSpan' => 11,
                'title' => $title,
                'noEntries' => ''
            ]], []]
        ];

        yield 'One log entry' => [
            'title' => $title,
            'setId' => $setId,
            'logEntries' => [
                [
                    'qid' => 20,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => 1674825269,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ]
            ],
            'expected' => [[[
                'titleRowSpan' => 1,
                'colSpan' => 11,
                'title' => $title,
                'trClass' => '',
                'qid' => [],
                'refresh' => [],
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
            ]], []]
        ];

        yield 'two log entries' => [
            'title' => $title,
            'setId' => $setId,
            'logEntries' => [
                [
                    'qid' => 20,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => 1674825269,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ],
                [
                    'qid' => 21,
                    'parameters' => '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
                    'scheduled' => 1674825269,
                    'set_id' => $setId,
                    'result_data' => '',
                    'exec_time' => 0,
                ]
            ],
            'expected' => [[[
                'titleRowSpan' => 2,
                'colSpan' => 11,
                'title' => $title,
                'trClass' => '',
                'qid' => [],
                'refresh' => [],
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
            ]], []]
        ];
    }
}
