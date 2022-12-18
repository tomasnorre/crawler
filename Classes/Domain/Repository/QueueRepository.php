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
use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @internal since v9.2.5
 */
class QueueRepository extends Repository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const TABLE_NAME = 'tx_crawler_queue';

    protected array $extensionSettings;

    #[Required]
    public function setExtensionSettings(): void
    {
        /** @var ExtensionConfigurationProvider $configurationProvider */
        $configurationProvider = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
        $this->extensionSettings = $configurationProvider->getExtensionConfiguration();
    }

    // TODO: Should be a property on the QueueObject
    public function unsetQueueProcessId(string $processId): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->update(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId)))
            ->set('process_id', '')
            ->executeStatement();
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
                $queryBuilder->expr()->eq(
                    'process_id_completed',
                    $queryBuilder->createNamedParameter($process->getProcessId())
                ),
                $queryBuilder->expr()->gt('exec_time', 0)
            )
            ->executeQuery()
            ->fetchOne();
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
            ->executeQuery()
            ->fetchOne();
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
            ->where($queryBuilder->expr()->eq('exec_time', 0))
            ->executeQuery()->fetchAllAssociative();
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
            ->executeQuery()
            ->fetchOne();
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
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Determines if a page is queued
     */
    public function isPageInQueue(
        int $uid,
        bool $unprocessed_only = true,
        bool $timed_only = false,
        int $timestamp = 0
    ): bool {
        $isPageInQueue = false;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $statement = $queryBuilder
            ->from(self::TABLE_NAME)
            ->count('*')
            ->where(
                $queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            );

        if ($unprocessed_only !== false) {
            $statement->andWhere($queryBuilder->expr()->eq('exec_time', 0));
        }

        if ($timed_only !== false) {
            $statement->andWhere($queryBuilder->expr()->neq('scheduled', 0));
        }

        if ($timestamp) {
            $statement->andWhere(
                $queryBuilder->expr()->eq('scheduled', $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT))
            );
        }

        // TODO: Currently it's not working if page doesn't exists. See tests
        $count = $statement
            ->executeQuery()
            ->fetchOne();

        if ($count !== false && $count > 0) {
            $isPageInQueue = true;
        }

        return $isPageInQueue;
    }

    public function cleanupQueue(): void
    {
        $extensionSettings = GeneralUtility::makeInstance(
            ExtensionConfigurationProvider::class
        )->getExtensionConfiguration();
        $purgeDays = (int) $extensionSettings['purgeQueueDays'];

        if ($purgeDays > 0) {
            $purgeDate = time() - 24 * 60 * 60 * $purgeDays;

            $queryBuilderDelete = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                self::TABLE_NAME
            );
            $del = $queryBuilderDelete
                ->delete(self::TABLE_NAME)
                ->where('exec_time != 0 AND exec_time < ' . $purgeDate)->executeStatement();

            if ($del === 0) {
                if ($this->logger !== null) {
                    $this->logger->info('No records was deleted');
                }
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
        $extensionSettings = GeneralUtility::makeInstance(
            ExtensionConfigurationProvider::class
        )->getExtensionConfiguration();
        // 24*60*60 Seconds in 24 hours
        $processedAgeInSeconds = $extensionSettings['cleanUpProcessedAge'] * 86400;
        $scheduledAgeInSeconds = $extensionSettings['cleanUpScheduledAge'] * 86400;

        $now = time();
        $condition = '(exec_time<>0 AND exec_time<' . ($now - $processedAgeInSeconds) . ') OR scheduled<=' . ($now - $scheduledAgeInSeconds);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $del = $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where($condition)->executeStatement();

        if ($del === 0) {
            if ($this->logger !== null) {
                $this->logger->info('No records was deleted.');
            }
        }
    }

    public function fetchRecordsToBeCrawled(int $countInARun): array
    {
        $queryBuilderSelect = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            self::TABLE_NAME
        );
        return $queryBuilderSelect
            ->select('qid', 'scheduled', 'page_id', 'sitemap_priority')
            ->from(self::TABLE_NAME)
            ->leftJoin(
                self::TABLE_NAME,
                'pages',
                'p',
                $queryBuilderSelect->expr()->eq(
                    'p.uid',
                    $queryBuilderSelect->quoteIdentifier(self::TABLE_NAME . '.page_id')
                )
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
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function updateProcessIdAndSchedulerForQueueIds(array $quidList, string $processId): int
    {
        $queryBuilderUpdate = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            self::TABLE_NAME
        );
        return $queryBuilderUpdate
            ->update(self::TABLE_NAME)
            ->where($queryBuilderUpdate->expr()->in('qid', $quidList))
            ->set('process_scheduled', (string) time())
            ->set('process_id', $processId)
            ->executeStatement();
    }

    public function unsetProcessScheduledAndProcessIdForQueueEntries(array $processIds): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->in(
                    'process_id',
                    $queryBuilder->createNamedParameter($processIds, Connection::PARAM_STR_ARRAY)
                )
            )
            ->set('process_scheduled', '0')
            ->set('process_id', '')
            ->executeStatement();
    }

    /**
     * This method is used to count if there are ANY unprocessed queue entries
     * of a given page_id and the configuration which matches a given hash.
     * If there if none, we can skip an inner detail check
     */
    public function noUnprocessedQueueEntriesForPageWithConfigurationHashExist(
        int $uid,
        string $configurationHash
    ): bool {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $noUnprocessedQueueEntriesFound = true;

        $result = $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('page_id', $uid),
                $queryBuilder->expr()->eq(
                    'configuration_hash',
                    $queryBuilder->createNamedParameter($configurationHash)
                ),
                $queryBuilder->expr()->eq('exec_time', 0)
            )
            ->executeQuery()
            ->fetchOne();

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
            ->executeStatement();
    }

    public function getDuplicateQueueItemsIfExists(
        bool $enableTimeslot,
        int $timestamp,
        int $currentTime,
        int $pageId,
        string $parametersHash
    ): array {
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
                    ->where('scheduled BETWEEN ' . $timeBegin . ' AND ' . $timeEnd . '')
                    ->orWhere($queryBuilder->expr()->lte('scheduled', $currentTime));
            } else {
                $queryBuilder
                    ->where($queryBuilder->expr()->lte('scheduled', $currentTime));
            }
        } elseif ($timestamp > $currentTime) {
            //entry with a timestamp in the future need to have the same schedule time
            $queryBuilder
                ->where($queryBuilder->expr()->eq('scheduled', $timestamp));
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq('exec_time', 0))
            ->andWhere($queryBuilder->expr()->eq('process_id', "''"))
            ->andWhere(
                $queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT))
            )
            ->andWhere(
                $queryBuilder->expr()->eq('parameters_hash', $queryBuilder->createNamedParameter(
                    $parametersHash,
                    \PDO::PARAM_STR
                ))
            );

        $statement = $queryBuilder->executeQuery();

        while ($row = $statement->fetchAssociative()) {
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
            ->where($queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($id, PDO::PARAM_INT)))
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

        return $queryBuilder->executeQuery()->fetchAllAssociative();
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
                $queryBuilder->expr()->eq(
                    'process_id_completed',
                    $queryBuilder->createNamedParameter($process->getProcessId())
                ),
                $queryBuilder->expr()->gt('exec_time', 0)
            )
            ->setMaxResults(1)
            ->addOrderBy($orderByField, $orderBySorting)
            ->executeQuery()->fetchAssociative();

        return $first ?: [];
    }
}
