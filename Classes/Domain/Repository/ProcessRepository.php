<?php
namespace AOE\Crawler\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
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
use AOE\Crawler\Domain\Model\ProcessCollection;
use AOE\Crawler\Utility\ExtensionSettingUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ProcessRepository
 *
 * @package AOE\Crawler\Domain\Repository
 */
class ProcessRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_process';

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder = QueryBuilder::class;

    /**
     * @var array
     */
    protected $extensionSettings = [];

    /**
     * QueueRepository constructor.
     */
    public function __construct()
    {
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $this->extensionSettings = ExtensionSettingUtility::loadExtensionSettings();
    }

    /**
     * This method is used to find all cli processes within a limit.
     *
     * @param  string $orderField
     * @param  string $orderDirection
     * @param  integer $itemCount
     * @param  integer $offset
     * @param  string $where
     *
     * @return ProcessCollection
     */
    public function findAll($orderField = '', $orderDirection = 'DESC', $itemCount = null, $offset = null, $where = '')
    {
        /** @var ProcessCollection $collection */
        $collection = GeneralUtility::makeInstance(ProcessCollection::class);

        $orderField = trim($orderField);
        $orderField = empty($orderField) ? 'process_id' : $orderField;

        $orderDirection = strtoupper(trim($orderDirection));
        $orderDirection = in_array($orderDirection, ['ASC', 'DESC']) ? $orderDirection : 'DESC';

        $rows = $this->getDB()->exec_SELECTgetRows(
            '*',
            $this->tableName,
            $where,
            '',
            htmlspecialchars($orderField) . ' ' . htmlspecialchars($orderDirection),
            $this->getLimitFromItemCountAndOffset($itemCount, $offset)
        );

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $collection->append(GeneralUtility::makeInstance(Process::class, $row));
            }
        }

        return $collection;
    }

    /**
     * Returns the number of active processes.
     *
     * @return integer
     */
    public function countActive()
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('active', 1),
                $this->queryBuilder->expr()->eq('deleted', 0)
            )
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * @return array|null
     *
     * Function is moved from ProcessCleanUpHook
     * TODO: Check why we need both getActiveProcessesOlderThanOneHour and getActiveOrphanProcesses, the get getActiveOrphanProcesses does not really check for Orphan in this implementation.
     */
    public function getActiveProcessesOlderThanOneHour()
    {
        $activeProcesses = [];
        $statement = $this->queryBuilder
            ->select('process_id', 'system_process_id')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->lte('ttl', intval(time() - $this->extensionSettings['processMaxRunTime'] - 3600)),
                $this->queryBuilder->expr()->eq('active', 1)
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
     * @see getActiveProcessesOlderThanOneHour
     * @return array
     *
     */
    public function getActiveOrphanProcesses()
    {
        $statement = $this->queryBuilder
            ->select('process_id', 'system_process_id')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->lte('ttl', intval(time() - $this->extensionSettings['processMaxRunTime'])),
                $this->queryBuilder->expr()->eq('active', 1)
            )
            ->execute()->fetchAll();

        return $statement;
    }

    /**
     * Returns the number of processes that live longer than the given timestamp.
     *
     * @param  integer $ttl
     *
     * @return integer
     */
    public function countNotTimeouted($ttl)
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('deleted', 0),
                $this->queryBuilder->expr()->gt('ttl', intval($ttl))
            )
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * Get limit clause
     *
     * @param  integer $itemCount
     * @param  integer $offset
     *
     * @return string
     */
    private function getLimitFromItemCountAndOffset($itemCount, $offset)
    {
        $itemCount = filter_var($itemCount, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 20]]);
        $offset = filter_var($offset, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'default' => 0]]);
        $limit = $offset . ', ' . $itemCount;

        return $limit;
    }

    /**
     * @return void
     */
    public function deleteProcessesWithoutItemsAssigned()
    {
        $this->queryBuilder
            ->delete($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('assigned_items_count', 0)
            )
            ->execute();
    }

    public function deleteProcessesMarkedAsDeleted()
    {
        $this->queryBuilder
            ->delete($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('deleted', 1)
            )
            ->execute();
    }

    /**
     * Returns an instance of the TYPO3 database class.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDB()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
