<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Domain\Repository;

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

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Value\QueueFilter;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class QueryRepositoryTest
 *
 * @package AOE\Crawler\Tests\Functional\Domain\Repository
 */
class QueueRepositoryTest extends FunctionalTestCase
{
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../../Fixtures/tx_crawler_queue.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/pages.xml');

        $this->subject = GeneralUtility::makeInstance(QueueRepository::class);
    }

    /**
     * @test
     *
     * @dataProvider getFirstOrLastObjectByProcessDataProvider
     */
    public function getFirstOrLastObjectByProcess(string $processId, string $orderBy, string $orderDirection, array $expected): void
    {
        $process = new Process();
        $process->setProcessId($processId);

        $mockedRepository = $this->getAccessibleMock(QueueRepository::class, ['dummy'], [], '', false);
        $result = $mockedRepository->_call('getFirstOrLastObjectByProcess', $process, $orderBy, $orderDirection);

        self::assertEquals(
            $expected,
            $result
        );
    }

    /**
     * @test
     */
    public function findYoungestEntryForProcessReturnsQueueEntry(): void
    {
        $process = new Process();
        $process->setProcessId('qwerty');

        self::assertEquals(
            [
                'qid' => '1002',
                'page_id' => '1002',
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
            $this->subject->findYoungestEntryForProcess($process)
        );
    }

    /**
     * @test
     */
    public function findOldestEntryForProcessReturnsQueueEntry(): void
    {
        $process = new Process();
        $process->setProcessId('qwerty');

        self::assertEquals(
            [
                'qid' => '1003',
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
            $this->subject->findOldestEntryForProcess($process)
        );
    }

    /**
     * @test
     */
    public function countExecutedItemsByProcessReturnsInteger(): void
    {
        $process = new Process();
        $process->setProcessId('qwerty');

        self::assertSame(
            2,
            intval($this->subject->countExecutedItemsByProcess($process))
        );
    }

    /**
     * @test
     */
    public function countNonExecutedItemsByProcessReturnsInteger(): void
    {
        $process = new Process();
        $process->setProcessId('1007');

        self::assertSame(
            2,
            $this->subject->countNonExecutedItemsByProcess($process)
        );
    }

    /**
     * @test
     */
    public function countAllPendingItemsExpectedNone(): void
    {
        $this->subject->flushQueue(new QueueFilter());
        self::assertSame(
            0,
            $this->subject->countAllPendingItems()
        );
    }

    /**
     * @test
     */
    public function countAllPendingItems(): void
    {
        self::assertSame(
            8,
            $this->subject->countAllPendingItems()
        );
    }

    /**
     * @test
     */
    public function countAllAssignedPendingItemsExpectedNone(): void
    {
        $this->subject->flushQueue(new QueueFilter());
        self::assertSame(
            0,
            $this->subject->countAllAssignedPendingItems()
        );
    }

    /**
     * @test
     */
    public function countAllAssignedPendingItems(): void
    {
        self::assertSame(
            3,
            $this->subject->countAllAssignedPendingItems()
        );
    }

    /**
     * @test
     */
    public function countAllUnassignedPendingItems(): void
    {
        self::assertSame(
            5,
            $this->subject->countAllUnassignedPendingItems()
        );
    }

    /**
     * @test
     */
    public function countPendingItemsGroupedByConfigurationKey(): void
    {
        $expectedArray = [
            0 => [
                'configuration' => 'FirstConfiguration',
                'unprocessed' => 3,
                'assigned_but_unprocessed' => 0,
            ],
            1 => [
                'configuration' => 'SecondConfiguration',
                'unprocessed' => 3,
                'assigned_but_unprocessed' => 1,
            ],
            2 => [
                'configuration' => 'ThirdConfiguration',
                'unprocessed' => 2,
                'assigned_but_unprocessed' => 2,
            ],
        ];

        $actualArray = $this->subject->countPendingItemsGroupedByConfigurationKey();

        foreach ($actualArray as $item) {
            self::assertTrue(in_array($item, $expectedArray));
        }
    }

    /**
     * @test
     */
    public function getSetIdWithUnprocessedEntries(): void
    {
        $expectedArray = [
            0 => 0,
            1 => 123,
            2 => 456,
            3 => 789,
        ];

        $actualArray = $this->subject->getSetIdWithUnprocessedEntries();
        foreach ($actualArray as $item) {
            self::assertTrue(in_array($item, $expectedArray));
        }
    }

    /**
     * @test
     */
    public function getTotalQueueEntriesByConfiguration(): void
    {
        $setIds = [123, 789];

        $expected = [
            'ThirdConfiguration' => 1,
            'SecondConfiguration' => 2,
        ];

        self::assertEquals(
            $expected,
            $this->subject->getTotalQueueEntriesByConfiguration($setIds)
        );
    }

    /**
     * @test
     */
    public function getLastProcessedEntriesTimestamps(): void
    {
        $expectedArray = [
            '0' => 20,
            '1' => 20,
            '2' => 18,
        ];

        self::assertEquals(
            $expectedArray,
            $this->subject->getLastProcessedEntriesTimestamps(3)
        );
    }

    /**
     * @test
     */
    public function getLastProcessedEntries(): void
    {
        $expectedArray = [1003, 1017];

        $processedEntries = $this->subject->getLastProcessedEntries(2);
        $actually = [];
        foreach ($processedEntries as $processedEntry) {
            $actually[] = $processedEntry['qid'];
        }

        // Todo: Figure out why there is a diff here
        // This is done as there is a difference in MySQL 5.6 and 8.0 in orders of the array.
        // A self::assertSame($a,$b) wasn't working on MySQL 8.0
        self::assertEmpty(
            array_diff($expectedArray, $actually)
        );
    }

    /**
     * @test
     */
    public function getPerformanceData(): void
    {
        $expected = [
            'asdfgh' => [
                'process_id_completed' => 'asdfgh',
                'start' => 10,
                'end' => 18,
                'urlcount' => 3,
            ],
            'qwerty' => [
                'process_id_completed' => 'qwerty',
                'start' => 10,
                'end' => 20,
                'urlcount' => 2,
            ],
            'dvorak' => [
                'process_id_completed' => 'dvorak',
                'start' => 10,
                'end' => 20,
                'urlcount' => 2,
            ],
        ];

        self::assertEquals(
            $expected,
            $this->subject->getPerformanceData(9, 21)
        );
    }

    /**
     * @test
     */
    public function countAll(): void
    {
        self::assertSame(
            15,
            $this->subject->countAll()
        );
    }

    /**
     * @test
     *
     * @dataProvider isPageInQueueDataProvider
     */
    public function isPageInQueue(int $uid, bool $unprocessed_only, bool $timed_only, int $timestamp, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->isPageInQueue($uid, $unprocessed_only, $timed_only, $timestamp)
        );
    }

    /**
     * @test
     */
    public function findByQueueId(): void
    {
        $queueRecord = $this->subject->findByQueueId('1015');
        self::assertSame(
            12,
            (int) $queueRecord['scheduled']
        );
    }

    /**
     * @test
     */
    public function cleanupQueue(): void
    {
        self::assertSame(15, $this->subject->findAll()->count());
        $this->subject->cleanupQueue();
        self::assertSame(8, $this->subject->findAll()->count());
    }

    /**
     * @test
     */
    public function cleanUpOldQueueEntries(): void
    {
        $recordsFromFixture = 15;
        $expectedRemainingRecords = 2;

        // Add records to queue repository to ensure we always have records,
        // that will not be deleted with the cleanUpOldQueueEntries-function
        $connectionForCrawlerQueue = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_queue');

        // Done for performance reason, as it gets repeated often
        $time = time() + (7 * 24 * 60 * 60);

        for ($i = 0; $i < $expectedRemainingRecords; $i++) {
            $connectionForCrawlerQueue
                ->insert(
                    'tx_crawler_queue',
                    [
                        'exec_time' => $time,
                        'scheduled' => $time,
                        'parameters' => 'not important parameters',
                        'result_data' => 'not important result_data',
                    ]
                );
        }

        // Check total entries before cleanup
        self::assertSame(
            $recordsFromFixture + $expectedRemainingRecords,
            $this->subject->findAll()->count()
        );

        $this->subject->cleanUpOldQueueEntries();

        // Check total entries after cleanup
        self::assertSame(
            $expectedRemainingRecords,
            $this->subject->findAll()->count()
        );
    }

    /**
     * @test
     */
    public function fetchRecordsToBeCrawled(): void
    {
        $recordsToBeCrawledLimitHigherThanRecordsCount = $this->subject->fetchRecordsToBeCrawled(10);
        self::assertCount(
            8,
            $recordsToBeCrawledLimitHigherThanRecordsCount
        );

        $recordsToBeCrawledLimitLowerThanRecordsCount = $this->subject->fetchRecordsToBeCrawled(3);
        self::assertCount(
            3,
            $recordsToBeCrawledLimitLowerThanRecordsCount
        );
    }

    /**
     * @test
     */
    public function fetchRecordsToBeCrawledCheckingPriority(): void
    {
        $recordsToBeCrawled = $this->subject->fetchRecordsToBeCrawled(5);

        $actualArray = [];
        foreach ($recordsToBeCrawled as $record) {
            $actualArray[] = $record['page_id'];
        }

        self::assertEquals(
            [1, 3, 5, 2, 4],
            $actualArray
        );
    }

    /**
     * @test
     */
    public function updateProcessIdAndSchedulerForQueueIds(): void
    {
        $qidToUpdate = [1004, 1008, 1015, 1018];
        $processId = md5('this-is-the-process-id');

        self::assertSame(
            4,
            $this->subject->updateProcessIdAndSchedulerForQueueIds($qidToUpdate, $processId)
        );
    }

    /**
     * @test
     */
    public function unsetProcessScheduledAndProcessIdForQueueEntries(): void
    {
        $unprocessedEntriesBefore = $this->subject->countAllUnassignedPendingItems();
        self::assertSame(
            5,
            $unprocessedEntriesBefore
        );
        $processIds = ['1007'];
        $this->subject->unsetProcessScheduledAndProcessIdForQueueEntries($processIds);

        $unprocessedEntriesAfter = $this->subject->countAllUnassignedPendingItems();
        self::assertSame(
            7,
            $unprocessedEntriesAfter
        );
    }

    /**
     * @dataProvider noUnprocessedQueueEntriesForPageWithConfigurationHashExistDataProvider
     */
    public function noUnprocessedQueueEntriesForPageWithConfigurationHashExist(int $uid, string $configurationHash, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->noUnprocessedQueueEntriesForPageWithConfigurationHashExist($uid, $configurationHash)
        );
    }

    /**
     * @test
     *
     * @dataProvider getQueueEntriesForPageIdDataProvider
     */
    public function getQueueEntriesForPageId(int $id, int $itemsPerPage, QueueFilter $queueFilter, array $expected): void
    {
        self::assertEquals(
            $expected,
            $this->subject->getQueueEntriesForPageId($id, $itemsPerPage, $queueFilter)
        );
    }

    /**
     * @test
     *
     * @dataProvider flushQueueDataProvider
     */
    public function flushQueue(QueueFilter $queueFilter, int $expected): void
    {
        $queryRepository = GeneralUtility::makeInstance(QueueRepository::class);
        $this->subject->flushQueue($queueFilter);

        self::assertSame(
            $expected,
            $queryRepository->findAll()->count()
        );
    }

    public function flushQueueDataProvider(): iterable
    {
        yield 'Flush Entire Queue' => [
            'filter' => new QueueFilter('all'),
            'expected' => 0,
        ];
        yield 'Flush Pending entries' => [
            'filter' => new QueueFilter('pending'),
            'expected' => 7,
        ];
        yield 'Flush Finished entries' => [
            'filter' => new QueueFilter('finished'),
            'expected' => 8,
        ];
    }

    /**
     * @test
     * @dataProvider getDuplicateQueueItemsIfExistsDataProvider
     */
    public function getDuplicateQueueItemsIfExists(bool $enableTimeslot, int $timestamp, int $currentTime, int $pageId, string $parametersHash, array $expected): void
    {
        $actual = $this->subject->getDuplicateQueueItemsIfExists($enableTimeslot, $timestamp, $currentTime, $pageId, $parametersHash);

        foreach ($actual as $item) {
            self::assertTrue(in_array($item, $expected));
        }
    }

    public function getDuplicateQueueItemsIfExistsDataProvider(): iterable
    {
        yield 'EnableTimeslot is true and timestamp is <= current' => [
            'timeslotActive' => true,
            'tstamp' => 10,
            'current' => 12,
            'page_id' => 10,
            'parameters_hash' => '',
            'expected' => [1018, 1020],
        ];
        yield 'EnableTimeslot is false and timestamp is <= current' => [
            'timeslotActive' => false,
            'tstamp' => 11,
            'current' => 11,
            'page_id' => 10,
            'parameters_hash' => '',
            'expected' => [1018],
        ];
        yield 'EnableTimeslot is true and timestamp is > current' => [
            'timeslotActive' => true,
            'tstamp' => 12,
            'current' => 10,
            'page_id' => 10,
            'parameters_hash' => '',
            'expected' => [1020],
        ];
        yield 'EnableTimeslot is false and timestamp is > current' => [
            'timeslotActive' => false,
            'tstamp' => 12,
            'current' => 10,
            'page_id' => 10,
            'parameters_hash' => '',
            'expected' => [1020],
        ];
        yield 'EnableTimeslot is false and timestamp is > current and parameters_hash is set' => [
            'timeslotActive' => false,
            'tstamp' => 12,
            'current' => 10,
            'page_id' => 10,
            'parameters_hash' => 'NotReallyAHashButWillDoForTesting',
            'expected' => [1019],
        ];
    }

    public function isPageInQueueDataProvider(): iterable
    {
        yield 'Unprocessed Only' => [
            'uid' => 10,
            'unprocessed_only' => true,
            'timed_only' => false,
            'timestamp' => 0,
            'expected' => true,
        ];
        yield 'Timed Only' => [
            'uid' => 16,
            'unprocessed_only' => false,
            'timed_only' => true,
            'timestamp' => 0,
            'expected' => true,
        ];
        yield 'Timestamp Only' => [
            'uid' => 17,
            'unprocessed_only' => false,
            'timed_only' => false,
            'timestamp' => 4321,
            'expected' => true,
        ];
        yield 'Not existing page' => [
            'uid' => 40000,
            'unprocessed_only' => false,
            'timed_only' => false,
            'timestamp' => 0,
            'expected' => false,
        ];
    }

    public function getFirstOrLastObjectByProcessDataProvider(): iterable
    {
        yield 'Known process_id, get first' => [
            'processId' => 'qwerty',
            'orderBy' => 'process_id',
            'orderDirection' => 'ASC',
            'expected' => [
                'qid' => '1002',
                'page_id' => '1002',
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
        ];
        yield 'Known process_id, get last' => [
            'processId' => 'qwerty',
            'orderBy' => 'process_id',
            'orderDirection' => 'DESC',
            'expected' => [
                'qid' => '1003',
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
        ];
        yield 'Unknown process_id' => [
            'processId' => 'unknown_id',
            'orderBy' => 'process_id',
            'orderDirection' => '',
            'expected' => [],
        ];
    }

    public function noUnprocessedQueueEntriesForPageWithConfigurationHashExistDataProvider(): iterable
    {
        yield 'No record found, uid not present' => [
            'uid' => 3000,
            'configurationHash' => '7b6919e533f334550b6f19034dfd2f81',
            'expected' => true,
        ];
        yield 'No record found, configurationHash not present' => [
            'uid' => 2001,
            'configurationHash' => 'invalidConfigurationHash',
            'expected' => true,
        ];
        yield 'Record found - uid and configurationHash is present' => [
            'uid' => 2001,
            'configurationHash' => '7b6919e533f334550b6f19034dfd2f81',
            'expected' => false,
        ];
    }

    public function getQueueEntriesForPageIdDataProvider(): iterable
    {
        yield 'Do Flush' => [
            'id' => 1002,
            'itemsPerPage' => 5,
            'queueFilter' => new QueueFilter('pending'),
            'expected' => [],
        ];
        yield 'Do Full Flush' => [
            'id' => 1002,
            'itemsPerPage' => 5,
            'queueFilter' => new QueueFilter('finished'),
            'expected' => [[
                'qid' => '1002',
                'page_id' => 1002,
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '',
                'scheduled' => 0,
                'exec_time' => 10,
                'result_data' => '',
                'process_scheduled' => 0,
                'process_id' => '1002',
                'process_id_completed' => 'qwerty',
                'configuration' => 'ThirdConfiguration',
                'set_id' => 0,
            ]],
        ];
        yield 'Check that doFullFlush do not flush if doFlush is not true' => [
            'id' => 2,
            'itemsPerPage' => 5,
            'queueFilter' => new QueueFilter(),
            'expected' => [[
                'qid' => '1006',
                'page_id' => '2',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '7b6919e533f334550b6f19034dfd2f81',
                'scheduled' => '0',
                'exec_time' => '0',
                'set_id' => '123',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1006',
                'process_id_completed' => 'qwerty',
                'configuration' => 'SecondConfiguration',
            ]],
        ];
        yield 'Get entries for page_id 2001' => [
            'id' => 2,
            'itemsPerPage' => 1,
            'queueFilter' => new QueueFilter(),
            'expected' => [[
                'qid' => '1006',
                'page_id' => '2',
                'parameters' => '',
                'parameters_hash' => '',
                'configuration_hash' => '7b6919e533f334550b6f19034dfd2f81',
                'scheduled' => '0',
                'exec_time' => '0',
                'set_id' => '123',
                'result_data' => '',
                'process_scheduled' => '0',
                'process_id' => '1006',
                'process_id_completed' => 'qwerty',
                'configuration' => 'SecondConfiguration',
            ]],
        ];
    }
}
