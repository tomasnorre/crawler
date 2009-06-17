<?php

require_once t3lib_extMgm::extPath('crawler') . 'domain/queue/class.tx_crawler_domain_queue_entry.php';

require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_repository.php';

class tx_crawler_domain_queue_repository extends tx_crawler_domain_lib_abstract_repository{
	
	
	protected $objectClassname = 'tx_crawler_domain_queue_entry';
	
	/**
	 * This mehtod is used to find the youngest entry for a given process.
	 * 
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param tx_crawler_domain_process $process
	 * @return tx_crawler_domain_queue_entry $entry
	 */
	public function findYoungestEntryForProcess(tx_crawler_domain_process $process){
		return $this->getFirstOrLastObjectByProcess($process,'exec_time ASC');
	}
	
	/**
	 * This method is used to find the oldest entry for a given process.
	 *
	 * @param tx_crawler_domain_process $process
	 * @return tx_crawler_domain_queue_entry
	 */
	public function findOldestEntryForProcess(tx_crawler_domain_process $process){
		return $this->getFirstOrLastObjectByProcess($process,'exec_time DESC');
	}
	

	/**
	 * This internal helper method is used to create an instance of an entry object
	 *
	 * @param tx_crawler_domain_process $process
	 * @param string $orderby first matching item will be returned as object
	 * @return tx_crawler_domain_queue_entry
	 */
	protected function getFirstOrLastObjectByProcess($process, $orderby){
		$db = $this->getDB();
		$where = 'process_id_completed='.$db->fullQuoteStr($process->getProcess_id(),$this->tableName).
				 ' AND exec_time > 0 ';
		$limit = 1;
		$groupby = '';
		
		$res 	= $db->exec_SELECTgetRows('*','tx_crawler_queue',$where,$groupby,$orderby,$limit);	
		if($res){
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
	public function countExtecutedItemsByProcess($process){
		
		return $this->countItemsByWhereClause('exec_time > 0 AND process_id_completed = '.$this->getDB()->fullQuoteStr($process->getProcess_id(),$this->tableName));
	}
	
	/**
	 * Counts items of a process which yet have not been processed/executed
	 *
	 * @param tx_crawler_domain_process $process
	 * @return int
	 */
	public function countNonExecutedItemsByProcess($process){
		return $this->countItemsByWhereClause('exec_time = 0 AND process_id = '.$this->getDB()->fullQuoteStr($process->getProcess_id(),$this->tableName));
	}
	
	/**
	 * This method can be used to count all queue entrys which are 
	 * scheduled for now or a earler date.
	 * 
	 * @param void
	 * @return int
	 */
	public function countAllPendingItems(){
		return $this->countItemsByWhereClause('exec_time = 0 AND scheduled < '.time());
	}

	/**
	 * This method can be used to count all queue entrys which are 
	 * scheduled for now or a earler date and are assigned to a process.
	 * 
	 * @param void
	 * @return int
	 */
	public function countAllAssignedPendingItems(){
		return $this->countItemsByWhereClause("exec_time = 0 AND scheduled < ".time()." AND process_id != ''");
	}
	
	/**
	 * Internal method to count items by a given where clause
	 *
	 */
	protected function countItemsByWhereClause($where){
		$db 	= $this->getDB();
		$rs 	= $db->exec_SELECTquery('count(*) as anz',$this->tableName,$where);
		$res 	= $db->sql_fetch_assoc($rs);

		return $res['anz']; 	
	}
	

}
?>