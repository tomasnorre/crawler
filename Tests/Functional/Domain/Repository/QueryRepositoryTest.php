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
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

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
     *
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
    public function getFirstOrLastObjectByProcess($processId, $orderBy, $expected)
    {
        /** @var Process $process */
        $process = new Process();
        $process->setProcessId($processId);

        $mockedRepository = $this->getAccessibleMock(QueueRepository::class, ['dummy']);
        /** @var Queue $result */
        $result = $mockedRepository->_call('getFirstOrLastObjectByProcess', $process, $orderBy);

        $this->assertSame(
            $expected,
            $result->getRow()
        );
    }

    /**
     * @test
     */
    public function findYoungestEntryForProcessReturnsQueueEntry()
    {
        $process = new Process();
        $process->setProcessId('qwerty');

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
                'configuration' => 'ThirdConfiguration'
            ],
            $this->subject->findYoungestEntryForProcess($process)->getRow()
        );
    }

    /**
     * @test
     */
    public function findOldestEntryForProcessReturnsQueueEntry()
    {
        $process = new Process();
        $process->setProcessId('qwerty');

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
                'configuration' => 'FirstConfiguration'
            ],
            $this->subject->findOldestEntryForProcess($process)->getRow()
        );
    }

    /**
     * @test
     */
    public function countExecutedItemsByProcessReturnsInteger()
    {
        $process = new Process();
        $process->setProcessId('qwerty');

        $this->assertSame(
            2,
            intval($this->subject->countExecutedItemsByProcess($process))
        );
    }

    /**
     * @test
     */
    public function countNonExecutedItemsByProcessReturnsInteger()
    {
        $process = new Process();
        $process->setProcessId('1007');

        $this->assertEquals(
            2,
            $this->subject->countNonExecutedItemsByProcess($process)
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
     *
     * @param string
     * @param int
     *
     * @dataProvider countItemsByWhereClauseDataProvider
     */
    public function countItemsByWhereClause($whereClause, $expected)
    {
        $mockedRepository = $this->getAccessibleMock(QueueRepository::class, ['dummy']);

        $this->assertEquals(
            $expected,
            $mockedRepository->_call('countItemsByWhereClause', $whereClause)
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
                'unprocessed' => '2',
                'assignedButUnprocessed' => '0'
            ],
            1 => [
                'configuration' => 'SecondConfiguration',
                'unprocessed' => '1',
                'assignedButUnprocessed' => '1'
            ],
            2 => [
                'configuration' => 'ThirdConfiguration',
                'unprocessed' => '2',
                'assignedButUnprocessed' => '2'
            ]
        ];

        $this->assertSame(
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
            3 => 789
        ];

        $this->assertSame(
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
            'SecondConfiguration' => '2',
            'ThirdConfiguration' => '1'
        ];

        $this->assertSame(
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
            '2' => 18
        ];

        $this->assertSame(
            $expectedArray,
            $this->subject->getLastProcessedEntriesTimestamps(3)
        );
    }

    /**
     * @test
     */
    public function getLastProcessedEntries()
    {
        $expectedArray = [
            ['qid' => '17'],
            ['qid' => '3']
        ];

        $this->assertSame(
            $expectedArray,
            $this->subject->getLastProcessedEntries('qid', 2)
        );
    }

    /**
     * @test
     */
    public function getPerformanceData()
    {
        $expected = [
            'asdfgh' => [
                'process_id_completed' => 'asdfgh',
                'start' => 10,
                'end' => 18,
                'urlcount' => 3
            ],
            'qwerty' => [
                'process_id_completed' => 'qwerty',
                'start' => 10,
                'end' => 20,
                'urlcount' => 2
            ],
            'dvorak' => [
                'process_id_completed' => 'dvorak',
                'start' => 10,
                'end' => 20,
                'urlcount' => 2
            ]
        ];

        $this->assertEquals(
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
                'orderBy' => 'process_id ASC',
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
                ]
            ],
            'Know process_id, get last' => [
                'processId' => 'qwerty',
                'orderBy' => 'process_id DESC',
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
                    'configuration' => 'FirstConfiguration'
                ]
            ],
            'Unknow process_id' => [
                'processId' => 'unknown_id',
                'orderBy' => '',
                'expected' => []
            ]
        ];
    }

    /**
     * @return array
     */
    public function countItemsByWhereClauseDataProvider()
    {
        return [
            'Empty where clause, expected to return all records' => [
                'whereClause' => '',
                'expected' => 12
            ],
            'Where Clause on process_id_completed' => [
                'whereClause' => 'process_id_completed = \'qwerty\'',
                'expected' => 3
            ]
        ];
    }
}
