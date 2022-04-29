<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Repository;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Value\QueueFilter;
use PDO;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @internal since v9.2.5
 */
class QueueRepository extends Repository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const TABLE_NAME = 'tx_crawler_queue';

    protected array $extensionSettings;

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();

        parent::__construct($objectManager);
    }

    // TODO: Should be a property on the QueueObject
    public function unsetQueueProcessId(string $processId): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->update(self::TABLE_NAME)
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        return (int) $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        return (int) $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('exec_time', 0)
            )
            ->execute()->fetchAll();
    }

    /**
     * This method can be used to count all queue entrys which are
     * scheduled for now or a earlier date.
     */
    public function countAllPendingItems(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        return (int) $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        return (int) $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->neq('process_id', "''"),
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->lte('scheduled', time())
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * This method can be used to count all queue entries which are
     * scheduled for now or an earlier date and are not assigned to a process.
     *
     * @deprecated since 11.0.4 will be removed when CrawlerApi is removed
     */
    public function countAllUnassignedPendingItems(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        return (int) $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('process_id', "''"),
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->lte('scheduled', time())
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Count pending queue entries grouped by configuration key
     * @deprecated since 11.0.4 will be removed when CrawlerApi is removed
     */
    public function countPendingItemsGroupedByConfigurationKey(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $statement = $queryBuilder
            ->from(self::TABLE_NAME)
            ->selectLiteral('count(*) as unprocessed', 'sum(case when process_id != \'\' then 1 else 0 end) as assigned_but_unprocessed')
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
     * @deprecated since 11.0.4 will be removed when CrawlerApi is removed
     */
    public function getSetIdWithUnprocessedEntries(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $statement = $queryBuilder
            ->select('set_id')
            ->from(self::TABLE_NAME)
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
     * @deprecated since 11.0.4 will be removed when CrawlerApi is removed
     */
    public function getTotalQueueEntriesByConfiguration(array $setIds): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $totals = [];
        if (! empty($setIds)) {
            $statement = $queryBuilder
                ->from(self::TABLE_NAME)
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
     * @deprecated since 11.0.4 will be removed when CrawlerApi is removed
     */
    public function getLastProcessedEntriesTimestamps($limit = 100): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $statement = $queryBuilder
            ->select('exec_time')
            ->from(self::TABLE_NAME)
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
     * @deprecated since 11.0.4 will be removed when CrawlerApi is removed
     */
    public function getLastProcessedEntries(int $limit = 100): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $statement = $queryBuilder
            ->from(self::TABLE_NAME)
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
     * @deprecated since 11.0.4 will be removed when CrawlerApi is removed
     */
    public function getPerformanceData($start, $end): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $statement = $queryBuilder
            ->from(self::TABLE_NAME)
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $statement = $queryBuilder
            ->from(self::TABLE_NAME)
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
     * @deprecated since 11.0.4 will be removed when CrawlerApi is removed
     */
    public function findByQueueId(string $queueId): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queueRec = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
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

            $queryBuilderDelete = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
            $del = $queryBuilderDelete
                ->delete(self::TABLE_NAME)
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
        // 24*60*60 Seconds in 24 hours
        $processedAgeInSeconds = $extensionSettings['cleanUpProcessedAge'] * 86400;
        $scheduledAgeInSeconds = $extensionSettings['cleanUpScheduledAge'] * 86400;

        $now = time();
        $condition = '(exec_time<>0 AND exec_time<' . ($now - $processedAgeInSeconds) . ') OR scheduled<=' . ($now - $scheduledAgeInSeconds);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $del = $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where(
                $condition
            )->execute();

        if ($del === false) {
            $this->logger->info(
                'Records could not be deleted.'
            );
        }
    }

    public function fetchRecordsToBeCrawled(int $countInARun): array
    {
        $queryBuilderSelect = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        return $queryBuilderSelect
            ->select('qid', 'scheduled', 'page_id', 'sitemap_priority')
            ->from(self::TABLE_NAME)
            ->leftJoin(
                self::TABLE_NAME,
                'pages',
                'p',
                $queryBuilderSelect->expr()->eq('p.uid', $queryBuilderSelect->quoteIdentifier(self::TABLE_NAME . '.page_id'))
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
        $queryBuilderUpdate = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        return $queryBuilderUpdate
            ->update(self::TABLE_NAME)
            ->where(
                $queryBuilderUpdate->expr()->in('qid', $quidList)
            )
            ->set('process_scheduled', (string) time())
            ->set('process_id', $processId)
            ->execute();
    }

    public function unsetProcessScheduledAndProcessIdForQueueEntries(array $processIds): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->in('process_id', $queryBuilder->createNamedParameter($processIds, Connection::PARAM_STR_ARRAY))
            )
            ->set('process_scheduled', '0')
            ->set('process_id', '')
            ->execute();
    }

    /**
     * This method is used to count if there are ANY unprocessed queue entries
     * of a given page_id and the configuration which matches a given hash.
     * If there if none, we can skip an inner detail check
     */
    public function noUnprocessedQueueEntriesForPageWithConfigurationHashExist(int $uid, string $configurationHash): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $noUnprocessedQueueEntriesFound = true;

        $result = $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('page_id', $uid),
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
     */
    public function flushQueue(QueueFilter $queueFilter): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        switch ($queueFilter) {
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
            ->delete(self::TABLE_NAME)
            ->execute();
    }

    public function getDuplicateQueueItemsIfExists(bool $enableTimeslot, int $timestamp, int $currentTime, int $pageId, string $parametersHash): array
    {
        $rows = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->select('qid')
            ->from(self::TABLE_NAME);
        //if this entry is scheduled with "now"
        if ($timestamp <= $currentTime) {
            if ($enableTimeslot) {
                $timeBegin = $currentTime - 100;
                $timeEnd = $currentTime + 100;
                $queryBuilder
                    ->where(
                        'scheduled BETWEEN ' . $timeBegin . ' AND ' . $timeEnd . ''
                    )
                    ->orWhere(
                        $queryBuilder->expr()->lte('scheduled', $currentTime)
                    );
            } else {
                $queryBuilder
                    ->where(
                        $queryBuilder->expr()->lte('scheduled', $currentTime)
                    );
            }
        } elseif ($timestamp > $currentTime) {
            //entry with a timestamp in the future need to have the same schedule time
            $queryBuilder
                ->where(
                    $queryBuilder->expr()->eq('scheduled', $timestamp)
                );
        }

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq('exec_time', 0)
            )
            ->andWhere(
                $queryBuilder->expr()->eq('process_id', "''")
            )
            ->andWhere($queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('parameters_hash', $queryBuilder->createNamedParameter($parametersHash, \PDO::PARAM_STR)));

        $statement = $queryBuilder->execute();

        while ($row = $statement->fetch()) {
            $rows[] = $row['qid'];
        }

        return $rows;
    }

    public function getQueueEntriesForPageId(int $id, int $itemsPerPage, QueueFilter $queueFilter): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($id, PDO::PARAM_INT))
            )
            ->orderBy('scheduled', 'DESC');

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME)
            ->getExpressionBuilder();
        $query = $expressionBuilder->andX();
        // PHPStorm adds the highlight that the $addWhere is immediately overwritten,
        // but the $query = $expressionBuilder->andX() ensures that the $addWhere is written correctly with AND
        // between the statements, it's not a mistake in the code.
        switch ($queueFilter) {
            case 'pending':
                $queryBuilder->andWhere($queryBuilder->expr()->eq('exec_time', 0));
                break;
            case 'finished':
                $queryBuilder->andWhere($queryBuilder->expr()->gt('exec_time', 0));
                break;
        }

        if ($itemsPerPage > 0) {
            $queryBuilder
                ->setMaxResults($itemsPerPage);
        }

        return $queryBuilder->execute()->fetchAll();
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $first = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
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
