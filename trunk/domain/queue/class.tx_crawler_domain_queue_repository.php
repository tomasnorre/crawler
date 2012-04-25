<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 AOE media (dev@aoemedia.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

require_once t3lib_extMgm::extPath('crawler') . 'domain/queue/class.tx_crawler_domain_queue_entry.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_repository.php';

class tx_crawler_domain_queue_repository extends tx_crawler_domain_lib_abstract_repository {


	protected $objectClassname = 'tx_crawler_domain_queue_entry';

	protected $tableName = 'tx_crawler_queue';

	/**
	 * This mehtod is used to find the youngest entry for a given process.
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param tx_crawler_domain_process $process
	 * @return tx_crawler_domain_queue_entry $entry
	 */
	public function findYoungestEntryForProcess(tx_crawler_domain_process $process) {
		return $this->getFirstOrLastObjectByProcess($process,'exec_time ASC');
	}

	/**
	 * This method is used to find the oldest entry for a given process.
	 *
	 * @param tx_crawler_domain_process $process
	 * @return tx_crawler_domain_queue_entry
	 */
	public function findOldestEntryForProcess(tx_crawler_domain_process $process) {
		return $this->getFirstOrLastObjectByProcess($process,'exec_time DESC');
	}


	/**
	 * This internal helper method is used to create an instance of an entry object
	 *
	 * @param tx_crawler_domain_process $process
	 * @param string $orderby first matching item will be returned as object
	 * @return tx_crawler_domain_queue_entry
	 */
	protected function getFirstOrLastObjectByProcess($process, $orderby) {
		$db = $this->getDB();
		$where = 'process_id_completed='.$db->fullQuoteStr($process->getProcess_id(),$this->tableName).
				 ' AND exec_time > 0 ';
		$limit = 1;
		$groupby = '';

		$res 	= $db->exec_SELECTgetRows('*','tx_crawler_queue',$where,$groupby,$orderby,$limit);
		if($res) {
			$first 	= $res[0];
		}else{
			$first = array();
		}
		$resultObject = new tx_crawler_domain_queue_entry($first);
		return $resultObject;
	}

	/**
	 * Counts all executed items of a process.
	 *
	 * @param tx_crawler_domain_process $process
	 * @return int
	 */
	public function countExtecutedItemsByProcess($process) {

		return $this->countItemsByWhereClause('exec_time > 0 AND process_id_completed = '.$this->getDB()->fullQuoteStr($process->getProcess_id(),$this->tableName));
	}

	/**
	 * Counts items of a process which yet have not been processed/executed
	 *
	 * @param tx_crawler_domain_process $process
	 * @return int
	 */
	public function countNonExecutedItemsByProcess($process) {
		return $this->countItemsByWhereClause('exec_time = 0 AND process_id = '.$this->getDB()->fullQuoteStr($process->getProcess_id(),$this->tableName));
	}

	/**
	 * This method can be used to count all queue entrys which are
	 * scheduled for now or a earler date.
	 *
	 * @param void
	 * @return int
	 */
	public function countAllPendingItems() {
		return $this->countItemsByWhereClause('exec_time = 0 AND scheduled < '.time());
	}

	/**
	 * This method can be used to count all queue entrys which are
	 * scheduled for now or a earler date and are assigned to a process.
	 *
	 * @param void
	 * @return int
	 */
	public function countAllAssignedPendingItems() {
		return $this->countItemsByWhereClause("exec_time = 0 AND scheduled < ".time()." AND process_id != ''");
	}
	
	/**
	 * This method can be used to count all queue entrys which are
	 * scheduled for now or a earler date and are not assigned to a process.
	 *
	 * @param void
	 * @return int
	 */
	public function countAllUnassignedPendingItems() {
		return $this->countItemsByWhereClause("exec_time = 0 AND scheduled < ".time()." AND process_id = ''");
	}

	/**
	 * Internal method to count items by a given where clause
	 *
	 */
	protected function countItemsByWhereClause($where) {
		$db 	= $this->getDB();
		$rs 	= $db->exec_SELECTquery('count(*) as anz',$this->tableName,$where);
		$res 	= $db->sql_fetch_assoc($rs);

		return $res['anz'];
	}

	/**
	 * Count pending queue entries grouped by configuration key
	 *
	 * @param void
	 * @return array
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2099-09-03
	 */
	public function countPendingItemsGroupedByConfigurationKey() {
		$db = $this->getDB();
		$res = $db->exec_SELECTquery(
			"configuration, count(*) as unprocessed, sum(process_id != '') as assignedButUnprocessed",
			$this->tableName,
			'exec_time = 0 AND scheduled < '.time(),
			'configuration'
		);
		$rows = array();
		while ($row = $db->sql_fetch_assoc($res)) {
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * Get set id with unprocessed entries
	 *
	 * @param void
	 * @return array array of set ids
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-03
	 */
	public function getSetIdWithUnprocessedEntries() {
		$db = $this->getDB();
		$res = $db->exec_SELECTquery(
			'set_id',
			$this->tableName,
			'exec_time = 0 AND scheduled < '.time(),
			'set_id'
		);
		$setIds = array();
		while ($row = $db->sql_fetch_assoc($res)) {
			$setIds[] = intval($row['set_id']);
		}
		return $setIds;
	}

	/**
	 * Get total queue entries by configuration
	 *
	 * @param array set ids
	 * @return array totals by configuration (keys)
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-03
	 */
	public function getTotalQueueEntriesByConfiguration(array $setIds) {
		$totals = array();
		if (count($setIds) > 0) {
			$db = $this->getDB();
			$res = $db->exec_SELECTquery(
				'configuration, count(*) as c',
				$this->tableName,
				'set_id in ('. implode(',',$setIds).') AND scheduled < '.time(),
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
	 * @param void
	 * @return array
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-03
	 */
	public function getLastProcessedEntriesTimestamps($limit = 100) {
		$db = $this->getDB();
		$res = $db->exec_SELECTquery(
			'exec_time',
			$this->tableName,
			'',
			'',
			'exec_time desc',
			$limit
		);

		$rows = array();
		while (($row = $db->sql_fetch_assoc($res)) !== false) {
			$rows[] = $row['exec_time'];
		}
		return $rows;
	}
	
	/**
	 * Get the last processed entries
	 * 
	 * @param string $selectFields
	 * @param int $limit
	 * @return array
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2010-11-16
	 */
	public function getLastProcessedEntries($selectFields='*', $limit='100') {
		$db = $this->getDB();
		$res = $db->exec_SELECTquery(
			$selectFields,
			$this->tableName,
			'',
			'',
			'exec_time desc',
			$limit
		);

		$rows = array();
		while (($row = $db->sql_fetch_assoc($res)) !== false) {
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * Get performance statistics data
	 *
	 * @param int start timestamp
	 * @param int end timestamp
	 * @return array performance data
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-03
	 */
	public function getPerformanceData($start, $end) {
		$db = $this->getDB();
		$res = $db->exec_SELECTquery(
			'process_id_completed, min(exec_time) as start, max(exec_time) as end, count(*) as urlcount',
			$this->tableName,
			'exec_time != 0 and exec_time >= '.intval($start). ' and exec_time <= ' . intval($end),
			'process_id_completed'
		);

		$rows = array();
		while (($row = $db->sql_fetch_assoc($res)) !== false) {
			$rows[$row['process_id_completed']] = $row;
		}
		return $rows;
	}

}

?>