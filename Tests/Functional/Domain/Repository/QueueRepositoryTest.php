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
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use AOE\Crawler\Value\QueueFilter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class QueryRepositoryTest
 *
 * @package AOE\Crawler\Tests\Functional\Domain\Repository
 */
class QueueRepositoryTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected \AOE\Crawler\Domain\Repository\QueueRepository $subject;

    /**
     * Creates the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupBackendRequest();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tx_crawler_queue.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');

        $this->subject = GeneralUtility::makeInstance(QueueRepository::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getFirstOrLastObjectByProcessDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getFirstOrLastObjectByProcess(
        string $processId,
        string $orderBy,
        string $orderDirection,
        array $expected
    ): void {
        $process = new Process();
        $process->setProcessId($processId);

        $mockedRepository = $this->getAccessibleMock(QueueRepository::class, null, [], '', false);
        $result = $mockedRepository->_call('getFirstOrLastObjectByProcess', $process, $orderBy, $orderDirection);

        self::assertEquals($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function countExecutedItemsByProcessReturnsInteger(): void
    {
        $process = new Process();
        $process->setProcessId('qwerty');

        self::assertSame(2, intval($this->subject->countExecutedItemsByProcess($process)));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countNonExecutedItemsByProcessReturnsInteger(): void
    {
        $process = new Process();
        $process->setProcessId('1007');

        self::assertSame(2, $this->subject->countNonExecutedItemsByProcess($process));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countAllPendingItemsExpectedNone(): void
    {
        $this->subject->flushQueue(new QueueFilter());
        self::assertSame(0, $this->subject->countAllPendingItems());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countAllPendingItems(): void
    {
        self::assertSame(8, $this->subject->countAllPendingItems());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countAllAssignedPendingItemsExpectedNone(): void
    {
        $this->subject->flushQueue(new QueueFilter());
        self::assertSame(0, $this->subject->countAllAssignedPendingItems());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countAllAssignedPendingItems(): void
    {
        self::assertSame(3, $this->subject->countAllAssignedPendingItems());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function countAll(): void
    {
        self::assertSame(15, $this->subject->countAll());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isPageInQueueDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function isPageInQueue(
        int $uid,
        bool $unprocessed_only,
        bool $timed_only,
        int $timestamp,
        bool $expected
    ): void {
        self::assertSame(
            $expected,
            $this->subject->isPageInQueue($uid, $unprocessed_only, $timed_only, $timestamp)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cleanupQueue(): void
    {
        self::assertSame(15, $this->subject->findAll()->count());
        $this->subject->cleanupQueue();
        self::assertSame(8, $this->subject->findAll()->count());
        // Called again to test the $del === 0 path of the cleanupQueue method.
        $this->subject->cleanupQueue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cleanUpOldQueueEntries(): void
    {
        $recordsFromFixture = 15;
        $expectedRemainingRecords = 2;

        // Add records to queue repository to ensure we always have records,
        // that will not be deleted with the cleanUpOldQueueEntries-function
        $connectionForCrawlerQueue = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(
            'tx_crawler_queue'
        );

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
        self::assertSame($recordsFromFixture + $expectedRemainingRecords, $this->subject->findAll()->count());

        $this->subject->cleanUpOldQueueEntries();

        // Check total entries after cleanup
        self::assertSame($expectedRemainingRecords, $this->subject->findAll()->count());

        // Called again to test the $del === 0 path of the cleanUpOldQueueEntries method.
        $this->subject->cleanUpOldQueueEntries();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchRecordsToBeCrawled(): void
    {
        $recordsToBeCrawledLimitHigherThanRecordsCount = $this->subject->fetchRecordsToBeCrawled(10);
        self::assertCount(8, $recordsToBeCrawledLimitHigherThanRecordsCount);

        $recordsToBeCrawledLimitLowerThanRecordsCount = $this->subject->fetchRecordsToBeCrawled(3);
        self::assertCount(3, $recordsToBeCrawledLimitLowerThanRecordsCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchRecordsToBeCrawledCheckingPriority(): void
    {
        $recordsToBeCrawled = $this->subject->fetchRecordsToBeCrawled(5);

        $actualArray = [];
        foreach ($recordsToBeCrawled as $record) {
            $actualArray[] = $record['page_id'];
        }

        self::assertEquals([1, 3, 5, 2, 4], $actualArray);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updateProcessIdAndSchedulerForQueueIds(): void
    {
        $qidToUpdate = [1004, 1008, 1015, 1018];
        $processId = md5('this-is-the-process-id');

        self::assertSame(4, $this->subject->updateProcessIdAndSchedulerForQueueIds($qidToUpdate, $processId));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unsetProcessScheduledAndProcessIdForQueueEntries(): void
    {
        $unprocessedEntriesBefore = $this->subject->countAllPendingItems() - $this->subject->countAllAssignedPendingItems();
        self::assertSame(5, $unprocessedEntriesBefore);
        $processIds = ['1007'];
        $this->subject->unsetProcessScheduledAndProcessIdForQueueEntries($processIds);

        $unprocessedEntriesAfter = $this->subject->countAllPendingItems() - $this->subject->countAllAssignedPendingItems();
        self::assertSame(7, $unprocessedEntriesAfter);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'noUnprocessedQueueEntriesForPageWithConfigurationHashExistDataProvider'
    )]
    public function noUnprocessedQueueEntriesForPageWithConfigurationHashExist(
        int $uid,
        string $configurationHash,
        bool $expected
    ): void {
        self::assertSame(
            $expected,
            $this->subject->noUnprocessedQueueEntriesForPageWithConfigurationHashExist($uid, $configurationHash)
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getQueueEntriesForPageIdDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getQueueEntriesForPageId(
        int $id,
        int $itemsPerPage,
        QueueFilter $queueFilter,
        array $expected
    ): void {
        self::assertEquals($expected, $this->subject->getQueueEntriesForPageId($id, $itemsPerPage, $queueFilter));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('flushQueueDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function flushQueue(QueueFilter $queueFilter, int $expected): void
    {
        $queryRepository = GeneralUtility::makeInstance(QueueRepository::class);
        $this->subject->flushQueue($queueFilter);

        self::assertSame($expected, $queryRepository->findAll()->count());
    }

    public static function flushQueueDataProvider(): iterable
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getDuplicateQueueItemsIfExistsDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getDuplicateQueueItemsIfExists(
        bool $enableTimeslot,
        int $timestamp,
        int $currentTime,
        int $pageId,
        string $parametersHash,
        array $expected
    ): void {
        $actual = $this->subject->getDuplicateQueueItemsIfExists(
            $enableTimeslot,
            $timestamp,
            $currentTime,
            $pageId,
            $parametersHash
        );

        foreach ($actual as $item) {
            self::assertTrue(in_array($item, $expected));
        }
    }

    public static function getDuplicateQueueItemsIfExistsDataProvider(): iterable
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

    public static function isPageInQueueDataProvider(): iterable
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

    public static function getFirstOrLastObjectByProcessDataProvider(): iterable
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

    public static function noUnprocessedQueueEntriesForPageWithConfigurationHashExistDataProvider(): iterable
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

    public static function getQueueEntriesForPageIdDataProvider(): iterable
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
