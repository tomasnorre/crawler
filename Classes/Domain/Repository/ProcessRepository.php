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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Model\ProcessCollection;
use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @internal since v9.2.5
 */
class ProcessRepository extends Repository
{
    public const TABLE_NAME = 'tx_crawler_process';

    protected QueryBuilder $queryBuilder;
    protected array $extensionSettings = [];

    public function __construct()
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if($typo3Version->getMajorVersion() <= 11) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            parent::__construct($objectManager);
        } else {
            parent::__construct();
        }

        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            self::TABLE_NAME
        );

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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        $statement = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->orderBy('ttl', 'DESC')
            ->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            $process = GeneralUtility::makeInstance(Process::class);
            $process->setProcessId((string) $row['process_id']);
            $process->setActive((bool) $row['active']);
            $process->setTtl((int) $row['ttl']);
            $process->setAssignedItemsCount((int) $row['assigned_items_count']);
            $process->setDeleted((bool) $row['deleted']);
            $process->setSystemProcessId((string) $row['system_process_id']);
            $collection->append($process);
        }

        return $collection;
    }

    public function findAllActive(): ProcessCollection
    {
        /** @var ProcessCollection $collection */
        $collection = GeneralUtility::makeInstance(ProcessCollection::class);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        $statement = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('active', 1), $queryBuilder->expr()->eq('deleted', 0))
            ->orderBy('ttl', 'DESC')
            ->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            $process = new Process();
            $process->setProcessId((string) $row['process_id']);
            $process->setActive((bool) $row['active']);
            $process->setTtl((int) $row['ttl']);
            $process->setAssignedItemsCount((int) $row['assigned_items_count']);
            $process->setDeleted((bool) $row['deleted']);
            $process->setSystemProcessId((string) $row['system_process_id']);
            $collection->append($process);
        }

        return $collection;
    }

    /**
     * @param string $processId
     */
    public function removeByProcessId($processId): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId, PDO::PARAM_STR))
            )->executeStatement();
    }

    /**
     * @return array|null
     *
     * Function is moved from ProcessCleanUpHook
     * TODO: Check why we need both getActiveProcessesOlderThanOneHour and getActiveOrphanProcesses, the get getActiveOrphanProcesses does not really check for Orphan in this implementation.
     */
    public function getActiveProcessesOlderThanOneHour()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $activeProcesses = [];
        $statement = $queryBuilder
            ->select('process_id', 'system_process_id')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->lte(
                    'ttl',
                    intval(time() - $this->extensionSettings['processMaxRunTime'] - 3600)
                ),
                $queryBuilder->expr()->eq('active', 1)
            )
            ->executeQuery();

        while ($row = $statement->fetchAssociative()) {
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        return $queryBuilder
            ->select('process_id', 'system_process_id')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->lte('ttl', intval(time() - $this->extensionSettings['processMaxRunTime'])),
                $queryBuilder->expr()->eq('active', 1)
            )
            ->executeQuery()->fetchAllAssociative();
    }

    /**
     * Returns the number of processes that live longer than the given timestamp.
     */
    public function countNotTimeouted(int $ttl): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);

        return (int) $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('deleted', 0), $queryBuilder->expr()->gt('ttl', intval($ttl)))
            ->executeQuery()
            ->fetchOne();
    }

    public function deleteProcessesWithoutItemsAssigned(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('assigned_items_count', 0))
            ->executeStatement();
    }

    public function deleteProcessesMarkedAsDeleted(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder
            ->delete(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('deleted', 1))
            ->executeStatement();
    }

    public function isProcessActive(string $processId): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $isActive = $queryBuilder
            ->select('active')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId)))
            ->orderBy('ttl')
            ->executeQuery()
            ->fetchFirstColumn();

        return (bool) $isActive;
    }

    public function updateProcessAssignItemsCount(int $numberOfAffectedRows, string $processId): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME)
            ->update(
                self::TABLE_NAME,
                ['assigned_items_count' => $numberOfAffectedRows],
                ['process_id' => $processId]
            );
    }

    public function markRequestedProcessesAsNotActive(array $processIds): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->update(self::TABLE_NAME)
            ->where(
                'NOT EXISTS (
                SELECT * FROM tx_crawler_queue
                    WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id
                    AND tx_crawler_queue.exec_time = 0
                )',
                $queryBuilder->expr()->in(
                    'process_id',
                    $queryBuilder->createNamedParameter($processIds, Connection::PARAM_STR_ARRAY)
                ),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->set('active', '0')
            ->executeStatement();
    }

    public function addProcess(string $processId, int $systemProcessId): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME)->insert(
            self::TABLE_NAME,
            [
                'process_id' => $processId,
                'active' => 1,
                'ttl' => time() + (int) $this->extensionSettings['processMaxRunTime'],
                'system_process_id' => $systemProcessId,
            ]
        );
    }
}
