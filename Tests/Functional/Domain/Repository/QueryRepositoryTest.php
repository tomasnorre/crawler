<?php
namespace AOE\Crawler\Tests\Functional\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Model\Queue;
use AOE\Crawler\Domain\Repository\QueueRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Class QueryRepositoryTest
 *
 * @package AOE\Crawler\Tests\Functional\Domain\Repository
 */
class QueryRepositoryTest extends FunctionalTestCase
{

    /**
     * @var array
     */
    //protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var QueueRepository
     */
    protected $subject;

    /**
     * Creates the test environment.
     */
    public function setUp()
    {
        parent::setUp();
        $this->importDataSet(dirname(__FILE__) . '/../../Fixtures/tx_crawler_queue.xml');
        $this->subject = new QueueRepository();
    }

    /**
     * @test
     *
     * @dataProvider getFirstOrLastObjectByProcessDataProvider
     */
    public function getFirstOrLastObjectByProcess($processId, $orderByField, $orderBySorting, $expected)
    {
        $process = new Process(['process_id' => $processId]);

        $mockedRepository = $this->getAccessibleMock(QueueRepository::class, ['dummy']);
        /** @var Queue $result */
        $result = $mockedRepository->_call('getFirstOrLastObjectByProcess', $process, $orderByField, $orderBySorting);

        $this->assertEquals(
            $expected,
            $result->getRow()
        );
    }

    /**
     * @test
     */
    public function findYoungestEntryForProcessReturnsQueueEntry()
    {
        $process = new Process(['process_id' => 'qwerty']);

        $this->assertEquals(
            [
                'qid' => '2',
                'page_id' => '0',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '',
                'scheduled' => '0',
                'exec_time' => '10',
                'set_id' => '0',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1002',
                'process_id_completed' => 'qwerty',
                'configuration' => 'ThirdConfiguration',
            ],
            $this->subject->findYoungestEntryForProcess($process)->getRow()
        );
    }

    /**
     * @test
     */
    public function findOldestEntryForProcessReturnsQueueEntry()
    {
        $process = new Process(['process_id' => 'qwerty']);

        $this->assertEquals(
            [
                'qid' => '3',
                'page_id' => '0',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '',
                'scheduled' => '0',
                'exec_time' => '20',
                'set_id' => '0',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1003',
                'process_id_completed' => 'qwerty',
                'configuration' => 'FirstConfiguration',
            ],
            $this->subject->findOldestEntryForProcess($process)->getRow()
        );
    }

    /**
     * @test
     */
    public function getAvailableSets()
    {
        $this->assertSame(
            $this->getExpectedSetsForGetAvailableSets(),
            $this->subject->getAvailableSets()
        );
    }

    /**
     * @test
     */
    public function isPageInQueueThrowInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1468931945);
        $this->subject->isPageInQueue('Cannot be interpreted as integer');
    }

    /**
     * @test
     *
     * @param $uid
     * @param $unprocessed_only
     * @param $timed_only
     * @param $timestamp
     * @param $expected
     *
     * @dataProvider isPageInQueueDataProvider
     */
    public function isPageInQueue($uid, $unprocessed_only, $timed_only, $timestamp, $expected)
    {
        $this->assertSame(
            $expected,
            $this->subject->isPageInQueue($uid, $unprocessed_only, $timed_only, $timestamp)
        );
    }

    /**
     * @test
     */
    public function isPageInQueueTimed()
    {
        $this->assertTrue($this->subject->isPageInQueueTimed(15));
    }

    /**
     * @return array
     */
    private function getExpectedSetsForGetAvailableSets()
    {
        return [
            [
                'count_value' => 1,
                'set_id' => 0,
                'scheduled' => 4321
            ],
            [
                'count_value' => 1,
                'set_id' => 0,
                'scheduled' => 1245
            ],
            [
                'count_value' => 6,
                'set_id' => 0,
                'scheduled' => 0
            ],
            [
                'count_value' => 2,
                'set_id' => 123,
                'scheduled' => 0
            ],
            [
                'count_value' => 1,
                'set_id' => 456,
                'scheduled' => 0
            ],
            [
                'count_value' => 1,
                'set_id' => 789,
                'scheduled' => 0
            ],
        ];
    }

    /**
     * @test
     */
    public function countExecutedItemsByProcessReturnsInteger()
    {
        $process = new Process(['process_id' => 'qwerty']);

        $this->assertEquals(
            2,
            $this->subject->countExecutedItemsByProcess($process)
        );
    }

    /**
     * @test
     */
    public function countNonExecutedItemsByProcessReturnsInteger()
    {
        $process = new Process(['process_id' => '1007']);

        $this->assertEquals(
            2,
            $this->subject->countNonExecutedItemsByProcess($process)
        );
    }

    /**
     * @test
     */
    public function getUnprocessedItems()
    {
        $expected = [ 4, 6, 8, 9, 15];

        // We only compare on qid to make the comparison easier
        $actually = [];
        foreach($this->subject->getUnprocessedItems() as $item) {
            $actually[] = $item['qid'];
        }

         $this->assertSame(
             $expected,
             $actually
         );

    }

    /**
     * @test
     */
    public function countUnprocessedItems()
    {
        $this->assertEquals(
            5,
            $this->subject->countUnprocessedItems()
        );
    }

    /**
     * @test
     */
    public function countAllPendingItems()
    {
        $this->assertEquals(
            5,
            $this->subject->countAllPendingItems()
        );
    }

    /**
     * @test
     */
    public function countAllAssignedPendingItems()
    {
        $this->assertEquals(
            3,
            $this->subject->countAllAssignedPendingItems()
        );
    }

    /**
     * @test
     */
    public function countAllUnassignedPendingItems()
    {
        $this->assertEquals(
            2,
            $this->subject->countAllUnassignedPendingItems()
        );
    }

    /**
     * @test
     */
    public function countPendingItemsGroupedByConfigurationKey()
    {
        $expectedArray = [
            0 => [
                'configuration' => 'FirstConfiguration',
                'unprocessed' => 2,
                'assignedButUnprocessed' => 0,
            ],
            1 => [
                'configuration' => 'SecondConfiguration',
                'unprocessed' => 1,
                'assignedButUnprocessed' => 1,
            ],
            2 => [
                'configuration' => 'ThirdConfiguration',
                'unprocessed' => 2,
                'assignedButUnprocessed' => 2,
            ],
        ];

        $this->assertEquals(
            $expectedArray,
            $this->subject->countPendingItemsGroupedByConfigurationKey()
        );
    }

    /**
     * @test
     */
    public function getSetIdWithUnprocessedEntries()
    {
        $expectedArray = [
            0 => 0,
            1 => 123,
            2 => 456,
            3 => 789,
        ];

        $this->assertEquals(
            $expectedArray,
            $this->subject->getSetIdWithUnprocessedEntries()
        );
    }

    /**
     * @test
     */
    public function getTotalQueueEntriesByConfiguration()
    {
        $setIds = [123,789];

        $expected = [
            'ThirdConfiguration' => 1,
            'SecondConfiguration' => 2,
        ];

        $this->assertEquals(
            $expected,
            $this->subject->getTotalQueueEntriesByConfiguration($setIds)
        );
    }

    /**
     * @test
     */
    public function getLastProcessedEntriesTimestamps()
    {
        $expectedArray = [
            '0' => 20,
            '1' => 20,
            '2' => 18,
        ];

        $this->assertEquals(
            $expectedArray,
            $this->subject->getLastProcessedEntriesTimestamps(3)
        );
    }

    /**
     * @test
     */
    public function getLastProcessedEntries()
    {
        $expectedArray = [3, 17];

        $processedEntries = $this->subject->getLastProcessedEntries(2);
        $actually = [];
        foreach($processedEntries as $processedEntry) {
            $actually[] = $processedEntry['qid'];
        }

        $this->assertSame(
            $expectedArray,
            $actually
        );
    }

    /**
     * @test
     */
    public function getPerformanceData()
    {
        $expected = [
            'asdfgh' => [
                'start' => 10,
                'end' => 18,
                'urlcount' => 3,
                'process_id_completed' => 'asdfgh',
            ],
            'dvorak' => [
                'start' => 10,
                'end' => 20,
                'urlcount' => 2,
                'process_id_completed' => 'dvorak',
            ],
            'qwerty' => [
                'start' => 10,
                'end' => 20,
                'urlcount' => 2,
                'process_id_completed' => 'qwerty',
            ],
        ];

        $this->assertSame(
            $expected,
            $this->subject->getPerformanceData(9, 21)
        );
    }

    /**
     * @test
     */
    public function countAll()
    {
        $this->assertEquals(
            12,
            $this->subject->countAll()
        );
    }

    /**
     * @return array
     */
    public function getFirstOrLastObjectByProcessDataProvider()
    {
        return [
            'Know process_id, get first' => [
                'processId' => 'qwerty',
                'orderByField' => 'process_id',
                'orderBySorting' => '',
                'expected' => [
                    'qid' => '2',
                    'page_id' => '0',
                    'parameters' => '',
                    'parameters_hash' => '',
                    'configuration_hash' => '',
                    'scheduled' => '0',
                    'exec_time' => '10',
                    'set_id' => '0',
                    'result_data' => '',
                    'process_scheduled' => '0',
                    'process_id' => '1002',
                    'process_id_completed' => 'qwerty',
                    'configuration' => 'ThirdConfiguration',
                ],
            ],
            'Know process_id, get last' => [
                'processId' => 'qwerty',
                'orderByField' => 'process_id',
                'orderBySorting' => 'DESC',
                'expected' => [
                    'qid' => '3',
                    'page_id' => '0',
                    'parameters' => '',
                    'parameters_hash' => '',
                    'configuration_hash' => '',
                    'scheduled' => '0',
                    'exec_time' => '20',
                    'set_id' => '0',
                    'result_data' => '',
                    'process_scheduled' => '0',
                    'process_id' => '1003',
                    'process_id_completed' => 'qwerty',
                    'configuration' => 'FirstConfiguration',
                ],
            ],

            // TODO: Adds Tests back for unknown id
            /*
             'Unknow process_id' => [
                'processId' => 'unknown_id',
                'orderByField' => '',
                'orderBySorting' => '',
                'expected' => []
            ]
            */
        ];
    }

    /**
     * @return array
     */
    public function isPageInQueueDataProvider()
    {
        return [
            'Unprocessed Only' => [
                'uid' => 15,
                'unprocessed_only' => true,
                'timed_only' => false,
                'timestamp' => false,
                'expected' => true
            ],
            'Timed Only' => [
                'uid' => 16,
                'unprocessed_only' => false,
                'timed_only' => true,
                'timestamp' => false,
                'expected' => true
            ],
            'Timestamp Only' => [
                'uid' => 17,
                'unprocessed_only' => false,
                'timed_only' => false,
                'timestamp' => 4321,
                'expected' => true
            ],
            // TODO: Adds Tests back for unknown id
            /*'Not existing page' => [
                'uid' => 40000,
                'unprocessed_only' => false,
                'timed_only' => false,
                'timestamp' => false,
                'expected' => false
            ],*/
        ];
    }
}
