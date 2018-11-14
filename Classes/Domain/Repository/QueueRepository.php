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
use AOE\Crawler\Domain\Model\Queue;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Class QueueRepository
 *
 * @package AOE\Crawler\Domain\Repository
 */
class QueueRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_queue';

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder = QueryBuilder::class;

    /**
     * QueueRepository constructor.
     */
    public function __construct()
    {
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
    }

    /**
     * @param $processId
     */
    public function unsetQueueProcessId($processId)
    {
        $this->queryBuilder
            ->update($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('process_id', $this->queryBuilder->createNamedParameter($processId))
            )
            ->set('process_id', '')
            ->execute();
    }

    /**
     * This method is used to find the youngest entry for a given process.
     *
     * @param Process $process
     *
     * @return Queue $entry
     */
    public function findYoungestEntryForProcess(Process $process)
    {
        return $this->getFirstOrLastObjectByProcess($process, 'exec_time');
    }

    /**
     * This method is used to find the oldest entry for a given process.
     *
     * @param Process $process
     *
     * @return Queue
     */
    public function findOldestEntryForProcess(Process $process)
    {
        return $this->getFirstOrLastObjectByProcess($process, 'exec_time', 'DESC');
    }

    /**
     * This internal helper method is used to create an instance of an entry object
     *
     * @param Process $process
     * @param string $orderByField first matching item will be returned as object
     * @param string $orderBySorting sorting direction
     *
     * @return Queue
     */
    protected function getFirstOrLastObjectByProcess($process, $orderByField, $orderBySorting = 'ASC')
    {

        $resultObject = new \stdClass();
        $first = $this->queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('process_id_completed', $this->queryBuilder->createNamedParameter($process->getProcess_id())),
                $this->queryBuilder->expr()->gt('exec_time', 0)
            )
            ->setMaxResults(1)
            ->addOrderBy($orderByField, $orderBySorting)
            ->execute();

        while ($row = $first->fetch()) {
            $resultObject = new Queue($row);
        }

        return $resultObject;
    }

    /**
     * Counts all executed items of a process.
     *
     * @param Process $process
     *
     * @return int
     */
    public function countExecutedItemsByProcess($process)
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('process_id_completed', $this->queryBuilder->createNamedParameter($process->getProcess_id())),
                $this->queryBuilder->expr()->gt('exec_time', 0)
            )
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * Counts items of a process which yet have not been processed/executed
     *
     * @param Process $process
     *
     * @return int
     */
    public function countNonExecutedItemsByProcess($process)
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('process_id', $this->queryBuilder->createNamedParameter($process->getProcess_id())),
                $this->queryBuilder->expr()->eq('exec_time', 0)
            )
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * get items which have not been processed yet
     *
     * @return array
     */
    public function getUnprocessedItems()
    {
        $unprocessedItems = $this->queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('exec_time', 0)
            )
            ->execute()->fetchAll();

        return $unprocessedItems;
    }

    /**
     * Count items which have not been processed yet
     * 
     * @return int
     */
    public function countUnprocessedItems()
    {
        return count($this->getUnprocessedItems());
    }

    /**
     * This method can be used to count all queue entrys which are
     * scheduled for now or a earlier date.
     *
     * @return int
     */
    public function countAllPendingItems()
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('process_scheduled', 0),
                $this->queryBuilder->expr()->eq('exec_time', 0),
                $this->queryBuilder->expr()->lte('scheduled', time())
            )
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * This method can be used to count all queue entrys which are
     * scheduled for now or a earlier date and are assigned to a process.
     *
     * @return int
     */
    public function countAllAssignedPendingItems()
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->neq('process_id', '""'),
                $this->queryBuilder->expr()->eq('exec_time', 0),
                $this->queryBuilder->expr()->lte('scheduled', time())
            )
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * This method can be used to count all queue entrys which are
     * scheduled for now or a earlier date and are not assigned to a process.
     *
     * @return int
     */
    public function countAllUnassignedPendingItems()
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('process_id', '""'),
                $this->queryBuilder->expr()->eq('exec_time', 0),
                $this->queryBuilder->expr()->lte('scheduled', time())
            )
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * Count pending queue entries grouped by configuration key
     *
     * @return array
     */
    public function countPendingItemsGroupedByConfigurationKey()
    {

        $db = $this->getDB();
        $res = $db->exec_SELECTquery(
            "configuration, count(*) as unprocessed, sum(process_id != '') as assignedButUnprocessed",
            $this->tableName,
            'exec_time = 0 AND scheduled < ' . time(),
            'configuration'
        );
        $rows = [];
        while ($row = $db->sql_fetch_assoc($res)) {
            $rows[] = $row;
        }

        return $rows;

/**
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('exec_time', 0),
                $this->queryBuilder->expr()->lte('scheduled', time())
            )
            ->addGroupBy(['configuration'])
            ->execute()
            ->fetchColumn(0);

        return $count;
**/
    }

    /**
     * Get set id with unprocessed entries
     *
     * @param void
     *
     * @return array array of set ids
     */
    public function getSetIdWithUnprocessedEntries()
    {
        $statement = $this->queryBuilder
            ->select('set_id')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->lt('scheduled', time()),
                $this->queryBuilder->expr()->eq('exec_time', 0)
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
     * @param array $setIds
     *
     * @return array totals by configuration (keys)
     */
    public function getTotalQueueEntriesByConfiguration(array $setIds)
    {
        $totals = [];
        if (count($setIds) > 0) {
            $db = $this->getDB();
            $res = $db->exec_SELECTquery(
                'configuration, count(*) as c',
                $this->tableName,
                'set_id in (' . implode(',', $setIds) . ') AND scheduled < ' . time(),
                'configuration'
            );
            while ($row = $db->sql_fetch_assoc($res)) {
                $totals[$row['configuration']] = $row['c'];
            }
        }

        return $totals;
    }

    /**
     * Get the timestamps of the last processed entries
     *
     * @param int $limit
     *
     * @return array
     */
    public function getLastProcessedEntriesTimestamps($limit = 100)
    {
        $statement = $this->queryBuilder
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
     *
     * @return array
     */
    public function getLastProcessedEntries($limit = 100)
    {
        $statement = $this->queryBuilder
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
    public function getPerformanceData($start, $end)
    {
        $statement = $this->queryBuilder
            ->from($this->tableName)
            ->selectLiteral('min(exec_time) as start', 'max(exec_time) as end', 'count(*) as urlcount')
            ->addSelect('process_id_completed')
            ->where(
                $this->queryBuilder->expr()->neq('exec_time', 0),
                $this->queryBuilder->expr()->gte('exec_time', $this->queryBuilder->createNamedParameter($start)),
                $this->queryBuilder->expr()->lte('exec_time', $this->queryBuilder->createNamedParameter($end))
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
     *
     * @param $uid
     * @param bool $unprocessed_only
     * @param bool $timed_only
     * @param bool $timestamp
     *
     * @return bool
     */
    public function isPageInQueue($uid, $unprocessed_only = true, $timed_only = false, $timestamp = false)
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            throw new \InvalidArgumentException('Invalid parameter type', 1468931945);
        }

        $isPageInQueue = false;

        $statement  = $this->queryBuilder
            ->from($this->tableName)
            ->count('*')
            ->where(
                $this->queryBuilder->expr()->eq('page_id', $this->queryBuilder->createNamedParameter($uid))
            );

        if (false !== $unprocessed_only) {
            $statement->andWhere(
                $this->queryBuilder->expr()->eq('exec_time', 0)
            );
        }

        if (false !== $timed_only) {
            $statement->andWhere(
                $this->queryBuilder->expr()->neq('scheduled', 0)
            );
        }

        if (false !== $timestamp) {
            $statement->andWhere(
                $this->queryBuilder->expr()->eq('scheduled', $this->queryBuilder->createNamedParameter($timestamp))
            );
        }

        // TODO: Currently it's not working if page doesn't exists. See tests
        $statement
            ->execute()
            ->fetchColumn(0);

        if (false !== $statement && $statement > 0) {
            $isPageInQueue = true;
        }

        return $isPageInQueue;
    }

    /**
     * Method to check if a page is in the queue which is timed for a
     * date when it should be crawled
     *
     * @param int $uid uid of the page
     * @param boolean $show_unprocessed only respect unprocessed pages
     *
     * @return boolean
     *
     */
    public function isPageInQueueTimed($uid, $show_unprocessed = true)
    {
        $uid = intval($uid);
        return $this->isPageInQueue($uid, $show_unprocessed);
    }


    /**
     * @return array
     */
    public function getAvailableSets()
    {
        $statement = $this->queryBuilder
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
}
