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

class tx_crawler_domain_process extends tx_crawler_domain_lib_abstract_dbobject {

	CONST STATE_RUNNING = 'running';
	CONST STATE_CANCELLED = 'cancelled';
	CONST STATE_COMPLETED = 'completed';

	/**
	 * @var string table name
	 */
	protected static $tableName = 'tx_crawler_process';

	/**
	 * Returns the activity state for this process
	 *
	 * @param void
	 * @return boolean
	 */
	public function getActive() {
		return $this->row['active'];
	}

	/**
	 * Returns the identifier for the process
	 *
	 * @return string
	 */
	public function getProcess_id() {
		return $this->row['process_id'];
	}

	/**
	 * Returns the timestamp of the exectime for the first relevant queue item.
	 * This can be used to determine the runtime
	 *
	 * @return int
	 */
	public function getTimeForFirstItem() {
		$queueRepository = new tx_crawler_domain_queue_repository();
		$entry = $queueRepository->findYoungestEntryForProcess($this);

		return $entry->getExecutionTime();
	}

	/**
	 * Returns the timestamp of the exectime for the last relevant queue item.
	 * This can be used to determine the runtime
	 *
	 * @return int
	 */
	public function getTimeForLastItem() {
		$queueRepository = new tx_crawler_domain_queue_repository();
		$entry = $queueRepository->findOldestEntryForProcess($this);

		return $entry->getExecutionTime();
	}

	/**
	 * Returns the difference between first and last processed item
	 *
	 * @return int
	 */
	public function getRuntime() {
		return $this->getTimeForLastItem() - $this->getTimeForFirstItem();
	}

	/**
	 * Returns the ttl of the process
	 *
	 * @return int
	 */
	public function getTTL() {
		return $this->row['ttl'];
	}

	/**
	 * Counts the number of items which need to be processed
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param void
	 * @return int
	 */
	public function countItemsProcessed() {
		$queueRepository = new tx_crawler_domain_queue_repository();
		return $queueRepository->countExtecutedItemsByProcess($this);
	}

	/**
	 * Counts the number of items which still need to be processed
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param void
	 * @return int
	 */
	public function countItemsToProcess() {
		$queueRepository = new tx_crawler_domain_queue_repository();
		return $queueRepository->countNonExecutedItemsByProcess($this);
	}

	/**
	 * Returns the Progress of a crawling process as a percentage value
	 *
	 * @param void
	 * @return float
	 */
	public function getProgress() {
		$all = $this->countItemsAssigned();
		if ($all<=0){
			return 0;
		}

		$res = round((100 / $all) * $this->countItemsProcessed());

		if($res > 100.0) {
			return 100.0;
		}
		return $res;
	}

	/**
	 * Returns the number of assigned Entrys
	 *
	 * @return int
	 */
	public function countItemsAssigned() {
		return $this->row['assigned_items_count'];
	}

	/**
	 * Return the processes current state
	 *
	 * @param void
	 * @return string 'running'|'cancelled'|'completed'
	 */
	public function getState() {
		if ($this->getActive() && $this->getProgress() < 100) {
			$stage = tx_crawler_domain_process::STATE_RUNNING;
		} elseif(!$this->getActive() && $this->getProgress() < 100) {
			$stage = tx_crawler_domain_process::STATE_CANCELLED;
		} else {
			$stage = tx_crawler_domain_process::STATE_COMPLETED;
		}
		return $stage;
	}
}