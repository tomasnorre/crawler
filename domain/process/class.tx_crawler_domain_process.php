<?php

require_once t3lib_extMgm::extPath('crawler') . 'domain/queue/class.tx_crawler_domain_queue_repository.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_dbobject.php';


class tx_crawler_domain_process extends tx_crawler_domain_lib_abstract_dbobject{

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
	 * Get table name
	 *
	 * @param void
	 * @return string table name
	 */
	public static function getTableName() {
		return self::$tableName;
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
		if ($all > 0) {
			$res = round((100 / $all) * $this->countItemsProcessed());
		} else {
			$res = 0;
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
		// TODO: use class constants for these states
		if ($this->getActive() && $this->getProgress() < 100) {
			$stage = 'running';
		} elseif(!$this->getActive() && $this->getProgress() < 100) {
			$stage = 'cancelled';
		} elseif(!$this->getActive() && $this->getProgress() >= 100) {
			$stage = 'completed';
		}
		return $stage;
	}
}
?>