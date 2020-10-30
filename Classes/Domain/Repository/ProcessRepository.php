<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
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
use AOE\Crawler\Domain\Model\ProcessCollection;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ProcessRepository
 *
 * @package AOE\Crawler\Domain\Repository
 */
class ProcessRepository extends Repository
{
    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_process';

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var array
     */
    protected $extensionSettings = [];

    /**
     * QueueRepository constructor.
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        parent::__construct($objectManager);

        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        /** @var ExtensionConfigurationProvider $configurationProvider */
        $configurationProvider = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
        $this->extensionSettings = $configurationProvider->getExtensionConfiguration();
    }

    /**
     * This method is used to find all cli processes within a limit.
     */
    public function findAll(): ProcessCollection
    {
        /** @var ProcessCollection $collection */
        $collection = GeneralUtility::makeInstance(ProcessCollection::class);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $statement = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->orderBy('ttl', 'DESC')
            ->execute();

        while ($row = $statement->fetch()) {
            $process = GeneralUtility::makeInstance(Process::class);
            $process->setProcessId($row['process_id']);
            $process->setActive($row['active']);
            $process->setTtl($row['ttl']);
            $process->setAssignedItemsCount($row['assigned_items_count']);
            $process->setDeleted($row['deleted']);
            $process->setSystemProcessId($row['system_process_id']);
            $collection->append($process);
        }

        return $collection;
    }

    /**
     * @param string $processId
     */
    public function findByProcessId($processId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId, \PDO::PARAM_STR))
            )->execute()->fetch(0);
    }

    public function findAllActive(): ProcessCollection
    {
        /** @var ProcessCollection $collection */
        $collection = GeneralUtility::makeInstance(ProcessCollection::class);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $statement = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('active', 1),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('ttl', 'DESC')
            ->execute();

        while ($row = $statement->fetch()) {
            $process = new Process();
            $process->setProcessId($row['process_id']);
            $process->setActive($row['active']);
            $process->setTtl($row['ttl']);
            $process->setAssignedItemsCount($row['assigned_items_count']);
            $process->setDeleted($row['deleted']);
            $process->setSystemProcessId($row['system_process_id']);
            $collection->append($process);
        }

        return $collection;
    }

    /**
     * @param string $processId
     */
    public function removeByProcessId($processId): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $queryBuilder
            ->delete($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId, \PDO::PARAM_STR))
            )->execute();
    }

    /**
     * Returns the number of active processes.
     *
     * @return int
     * @deprecated Using ProcessRepository->countActive() is deprecated since 9.1.1 and will be removed in v11.x, please use ProcessRepository->findAllActive->count() instead
     */
    public function countActive()
    {
        return $this->findAllActive()->count();
    }

    /**
     * @return array|null
     *
     * Function is moved from ProcessCleanUpHook
     * TODO: Check why we need both getActiveProcessesOlderThanOneHour and getActiveOrphanProcesses, the get getActiveOrphanProcesses does not really check for Orphan in this implementation.
     */
    public function getActiveProcessesOlderThanOneHour()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $activeProcesses = [];
        $statement = $queryBuilder
            ->select('process_id', 'system_process_id')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->lte('ttl', intval(time() - $this->extensionSettings['processMaxRunTime'] - 3600)),
                $queryBuilder->expr()->eq('active', 1)
            )
            ->execute();

        while ($row = $statement->fetch()) {
            $activeProcesses[] = $row;
        }

        return $activeProcesses;
    }

    /**
     * Function is moved from ProcessCleanUpHook
     *
     * @return array
     * @see getActiveProcessesOlderThanOneHour
     */
    public function getActiveOrphanProcesses()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->select('process_id', 'system_process_id')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->lte('ttl', intval(time() - $this->extensionSettings['processMaxRunTime'])),
                $queryBuilder->expr()->eq('active', 1)
            )
            ->execute()->fetchAll();
    }

    /**
     * Returns the number of processes that live longer than the given timestamp.
     */
    public function countNotTimeouted(int $ttl): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->gt('ttl', intval($ttl))
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get limit clause
     * @deprecated Using ProcessRepository::getLimitFromItemCountAndOffset() is deprecated since 9.1.1 and will be removed in v11.x, was not used, so will be removed
     */
    public static function getLimitFromItemCountAndOffset(int $itemCount, int $offset): string
    {
        $itemCount = filter_var($itemCount, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 20]]);
        $offset = filter_var($offset, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'default' => 0]]);

        return $offset . ', ' . $itemCount;
    }

    public function deleteProcessesWithoutItemsAssigned(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->delete($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('assigned_items_count', 0)
            )
            ->execute();
    }

    public function deleteProcessesMarkedAsDeleted(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->delete($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('deleted', 1)
            )
            ->execute();
    }

    public function isProcessActive(string $processId): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $isActive = $queryBuilder
            ->select('active')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId))
            )
            ->orderBy('ttl')
            ->execute()
            ->fetchColumn(0);

        return (bool) $isActive;
    }

    /**
     * @param $numberOfAffectedRows
     */
    public function updateProcessAssignItemsCount($numberOfAffectedRows, string $processId): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName)
            ->update(
                'tx_crawler_process',
                ['assigned_items_count' => (int) $numberOfAffectedRows],
                ['process_id' => $processId]
            );
    }

    public function markRequestedProcessesAsNotActive(array $processIds): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder->update('tx_crawler_process')
            ->where(
                'NOT EXISTS (
                SELECT * FROM tx_crawler_queue
                    WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id
                    AND tx_crawler_queue.exec_time = 0
                )',
                $queryBuilder->expr()->in('process_id', $queryBuilder->createNamedParameter($processIds, Connection::PARAM_STR_ARRAY)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->set('active', 0)
            ->execute();
    }
}
