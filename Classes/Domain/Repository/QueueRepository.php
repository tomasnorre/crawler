<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Model\Process;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class QueueRepository
 *
 * @package AOE\Crawler\Domain\Repository
 */
class QueueRepository extends Repository implements LoggerAwareInterface
{
    use \Psr\Log\LoggerAwareTrait;

    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_queue';

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        parent::__construct($objectManager);
    }

    public function unsetQueueProcessId(string $processId): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->update($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId))
            )
            ->set('process_id', '')
            ->execute();
    }

    /**
     * This method is used to find the youngest entry for a given process.
     */
    public function findYoungestEntryForProcess(Process $process): array
    {
        return $this->getFirstOrLastObjectByProcess($process, 'exec_time');
    }

    /**
     * This method is used to find the oldest entry for a given process.
     */
    public function findOldestEntryForProcess(Process $process): array
    {
        return $this->getFirstOrLastObjectByProcess($process, 'exec_time', 'DESC');
    }

    /**
     * Counts all executed items of a process.
     *
     * @param Process $process
     */
    public function countExecutedItemsByProcess($process): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id_completed', $queryBuilder->createNamedParameter($process->getProcessId())),
                $queryBuilder->expr()->gt('exec_time', 0)
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Counts items of a process which yet have not been processed/executed
     *
     * @param Process $process
     */
    public function countNonExecutedItemsByProcess($process): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($process->getProcessId())),
                $queryBuilder->expr()->eq('exec_time', 0)
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * get items which have not been processed yet
     */
    public function getUnprocessedItems(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('exec_time', 0)
            )
            ->execute()->fetchAll();
    }

    /**
     * Count items which have not been processed yet
     */
    public function countUnprocessedItems(): int
    {
        return count($this->getUnprocessedItems());
    }

    /**
     * This method can be used to count all queue entrys which are
     * scheduled for now or a earlier date.
     */
    public function countAllPendingItems(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_scheduled', 0),
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->lte('scheduled', time())
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * This method can be used to count all queue entries which are
     * scheduled for now or a earlier date and are assigned to a process.
     */
    public function countAllAssignedPendingItems(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->neq('process_id', '""'),
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->lte('scheduled', time())
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * This method can be used to count all queue entrys which are
     * scheduled for now or a earlier date and are not assigned to a process.
     */
    public function countAllUnassignedPendingItems(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id', '""'),
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->lte('scheduled', time())
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Count pending queue entries grouped by configuration key
     */
    public function countPendingItemsGroupedByConfigurationKey(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->from($this->tableName)
            ->selectLiteral('count(*) as unprocessed', 'sum(process_id != \'\') as assignedButUnprocessed')
            ->addSelect('configuration')
            ->where(
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->lt('scheduled', time())
            )
            ->groupBy('configuration')
            ->execute();

        return $statement->fetchAll();
    }

    /**
     * Get set id with unprocessed entries
     *
     * @return array array of set ids
     */
    public function getSetIdWithUnprocessedEntries(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->select('set_id')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->lt('scheduled', time()),
                $queryBuilder->expr()->eq('exec_time', 0)
            )
            ->addGroupBy('set_id')
            ->execute();

        $setIds = [];
        while ($row = $statement->fetch()) {
            $setIds[] = intval($row['set_id']);
        }

        return $setIds;
    }

    /**
     * Get total queue entries by configuration
     *
     * @return array totals by configuration (keys)
     */
    public function getTotalQueueEntriesByConfiguration(array $setIds): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $totals = [];
        if (count($setIds) > 0) {
            $statement = $queryBuilder
                ->from($this->tableName)
                ->selectLiteral('count(*) as c')
                ->addSelect('configuration')
                ->where(
                    $queryBuilder->expr()->in('set_id', implode(',', $setIds)),
                    $queryBuilder->expr()->lt('scheduled', time())
                )
                ->groupBy('configuration')
                ->execute();

            while ($row = $statement->fetch()) {
                $totals[$row['configuration']] = $row['c'];
            }
        }

        return $totals;
    }

    /**
     * Get the timestamps of the last processed entries
     *
     * @param int $limit
     */
    public function getLastProcessedEntriesTimestamps($limit = 100): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->select('exec_time')
            ->from($this->tableName)
            ->addOrderBy('exec_time', 'desc')
            ->setMaxResults($limit)
            ->execute();

        $rows = [];
        while ($row = $statement->fetch()) {
            $rows[] = $row['exec_time'];
        }

        return $rows;
    }

    /**
     * Get the last processed entries
     *
     * @param int $limit
     */
    public function getLastProcessedEntries($limit = 100): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->from($this->tableName)
            ->select('*')
            ->orderBy('exec_time', 'desc')
            ->setMaxResults($limit)
            ->execute();

        $rows = [];
        while (($row = $statement->fetch()) !== false) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Get performance statistics data
     *
     * @param int $start timestamp
     * @param int $end timestamp
     *
     * @return array performance data
     */
    public function getPerformanceData($start, $end): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->from($this->tableName)
            ->selectLiteral('min(exec_time) as start', 'max(exec_time) as end', 'count(*) as urlcount')
            ->addSelect('process_id_completed')
            ->where(
                $queryBuilder->expr()->neq('exec_time', 0),
                $queryBuilder->expr()->gte('exec_time', $queryBuilder->createNamedParameter($start, \PDO::PARAM_INT)),
                $queryBuilder->expr()->lte('exec_time', $queryBuilder->createNamedParameter($end, \PDO::PARAM_INT))
            )
            ->groupBy('process_id_completed')
            ->execute();

        $rows = [];
        while ($row = $statement->fetch()) {
            $rows[$row['process_id_completed']] = $row;
        }

        return $rows;
    }

    /**
     * Determines if a page is queued
     */
    public function isPageInQueue(int $uid, bool $unprocessed_only = true, bool $timed_only = false, int $timestamp = 0): bool
    {
        $isPageInQueue = false;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->from($this->tableName)
            ->count('*')
            ->where(
                $queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            );

        if ($unprocessed_only !== false) {
            $statement->andWhere(
                $queryBuilder->expr()->eq('exec_time', 0)
            );
        }

        if ($timed_only !== false) {
            $statement->andWhere(
                $queryBuilder->expr()->neq('scheduled', 0)
            );
        }

        if ($timestamp) {
            $statement->andWhere(
                $queryBuilder->expr()->eq('scheduled', $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT))
            );
        }

        // TODO: Currently it's not working if page doesn't exists. See tests
        $count = $statement
            ->execute()
            ->fetchColumn(0);

        if ($count !== false && $count > 0) {
            $isPageInQueue = true;
        }

        return $isPageInQueue;
    }

    /**
     * Method to check if a page is in the queue which is timed for a
     * date when it should be crawled
     */
    public function isPageInQueueTimed(int $uid, bool $show_unprocessed = true): bool
    {
        return $this->isPageInQueue($uid, $show_unprocessed);
    }

    public function getAvailableSets(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->selectLiteral('count(*) as count_value')
            ->addSelect('set_id', 'scheduled')
            ->from($this->tableName)
            ->orderBy('scheduled', 'desc')
            ->groupBy('set_id', 'scheduled')
            ->execute();

        $rows = [];
        while ($row = $statement->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function findByQueueId(string $queueId): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queueRec = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('qid', $queryBuilder->createNamedParameter($queueId))
            )
            ->execute()
            ->fetch();
        return is_array($queueRec) ? $queueRec : null;
    }

    public function cleanupQueue(): void
    {
        $extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $purgeDays = (int) $extensionSettings['purgeQueueDays'];

        if ($purgeDays > 0) {
            $purgeDate = time() - 24 * 60 * 60 * $purgeDays;

            $queryBuilderDelete = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
            $del = $queryBuilderDelete
                ->delete($this->tableName)
                ->where(
                    'exec_time != 0 AND exec_time < ' . $purgeDate
                )->execute();

            if ($del === false) {
                $this->logger->info(
                    'Records could not be deleted.'
                );
            }
        }
    }

    /**
     * Cleans up entries that stayed for too long in the queue. These are default:
     * - processed entries that are over 1.5 days in age
     * - scheduled entries that are over 7 days old
     */
    public function cleanUpOldQueueEntries(): void
    {
        $extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $processedAgeInSeconds = $extensionSettings['cleanUpProcessedAge'] * 86400; // 24*60*60 Seconds in 24 hours
        $scheduledAgeInSeconds = $extensionSettings['cleanUpScheduledAge'] * 86400;

        $now = time();
        $condition = '(exec_time<>0 AND exec_time<' . ($now - $processedAgeInSeconds) . ') OR scheduled<=' . ($now - $scheduledAgeInSeconds);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $del = $queryBuilder
            ->delete($this->tableName)
            ->where(
                $condition
            )->execute();

        if ($del === false) {
            $this->logger->info(
                'Records could not be deleted.'
            );
        }
    }

    public function fetchRecordsToBeCrawled(int $countInARun)
    {
        $queryBuilderSelect = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        return $queryBuilderSelect
            ->select('qid', 'scheduled', 'page_id', 'sitemap_priority')
            ->from($this->tableName)
            ->leftJoin(
                $this->tableName,
                'pages',
                'p',
                $queryBuilderSelect->expr()->eq('p.uid', $queryBuilderSelect->quoteIdentifier($this->tableName . '.page_id'))
            )
            ->where(
                $queryBuilderSelect->expr()->eq('exec_time', 0),
                $queryBuilderSelect->expr()->eq('process_scheduled', 0),
                $queryBuilderSelect->expr()->lte('scheduled', time())
            )
            ->orderBy('sitemap_priority', 'DESC')
            ->addOrderBy('scheduled')
            ->addOrderBy('qid')
            ->setMaxResults($countInARun)
            ->execute()
            ->fetchAll();
    }

    public function updateProcessIdAndSchedulerForQueueIds(array $quidList, string $processId)
    {
        $queryBuilderUpdate = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        return $queryBuilderUpdate
            ->update($this->tableName)
            ->where(
                $queryBuilderUpdate->expr()->in('qid', $quidList)
            )
            ->set('process_scheduled', time())
            ->set('process_id', $processId)
            ->execute();
    }

    public function unsetProcessScheduledAndProcessIdForQueueEntries(array $processIds): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->update($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->in('process_id', $queryBuilder->createNamedParameter($processIds, Connection::PARAM_STR_ARRAY))
            )
            ->set('process_scheduled', 0)
            ->set('process_id', '')
            ->execute();
    }

    /**
     * This method is used to count if there are ANY unprocessed queue entries
     * of a given page_id and the configuration which matches a given hash.
     * If there if none, we can skip an inner detail check
     *
     * @param int $uid
     * @param string $configurationHash
     * @return boolean
     */
    public function noUnprocessedQueueEntriesForPageWithConfigurationHashExist($uid, $configurationHash): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $noUnprocessedQueueEntriesFound = true;

        $result = $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('page_id', (int) $uid),
                $queryBuilder->expr()->eq('configuration_hash', $queryBuilder->createNamedParameter($configurationHash)),
                $queryBuilder->expr()->eq('exec_time', 0)
            )
            ->execute()
            ->fetchColumn();

        if ($result) {
            $noUnprocessedQueueEntriesFound = false;
        }

        return $noUnprocessedQueueEntriesFound;
    }

    /**
     * Removes queue entries
     *
     * @param string $filter all, pending, finished
     */
    public function flushQueue(string $filter): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        switch (strtolower($filter)) {
            case 'all':
                // No where claus needed delete everything
                break;
            case 'pending':
                $queryBuilder->andWhere($queryBuilder->expr()->eq('exec_time', 0));
                break;
            case 'finished':
            default:
                $queryBuilder->andWhere($queryBuilder->expr()->gt('exec_time', 0));
                break;
        }

        $queryBuilder
            ->delete($this->tableName)
            ->execute();
    }

    /**
     * @param string $processId
     *
     * @return bool|string
     */
    public function countAllByProcessId($processId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId, \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * This internal helper method is used to create an instance of an entry object
     *
     * @param Process $process
     * @param string $orderByField first matching item will be returned as object
     * @param string $orderBySorting sorting direction
     */
    protected function getFirstOrLastObjectByProcess($process, $orderByField, $orderBySorting = 'ASC'): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $first = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id_completed', $queryBuilder->createNamedParameter($process->getProcessId())),
                $queryBuilder->expr()->gt('exec_time', 0)
            )
            ->setMaxResults(1)
            ->addOrderBy($orderByField, $orderBySorting)
            ->execute()->fetch(0);

        return $first ?: [];
    }
}
