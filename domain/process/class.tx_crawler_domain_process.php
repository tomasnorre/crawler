<?php

require_once t3lib_extMgm::extPath('crawler') . 'domain/queue/class.tx_crawler_domain_queue_repository.php';

class tx_crawler_domain_process{
	/**
	 * @var array
	 */
	protected $row;
	
	/**
	 * Method to initialize the process object
	 *
	 * @param array $row
	 */
	public function __construct($row = array()){
		$this->row = $row;
	}
	

	/**
	 * Returns the activity state for this process
	 *
	 * @return boolean
	 */
	public function getActive(){
		return $this->row['active'];
	}
	
	/**
	 * Returns the identifier for the process
	 *
	 * @return string
	 */
	public function getProcess_id(){
		return $this->row['process_id'];
	}
	
	/**
	 * Returns the timestamp of the exectime for the first relevant queue item.
	 * This can be used to determine the runtime
	 *
	 * @return int
	 */
	public function getTimeForFirstItem(){
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
	public function getTimeForLastItem(){
		$queueRepository = new tx_crawler_domain_queue_repository();
		$entry = $queueRepository->findOldestEntryForProcess($this);
		
		return $entry->getExecutionTime();
	}
	
	/**
	 * Returns the difference between first and last processed item
	 *
	 * @return int
	 */
	public function getRuntime(){
		return $this->getTimeForLastItem() - $this->getTimeForFirstItem();
	}
	
	/**
	 * Returns the ttl of the process
	 *
	 * @return int
	 */
	public function getTTL(){
		return $this->row['ttl'];
	}
	
	/**
	 * Counts the number of items which need to be processed
	 * 
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param void
	 * @return int
	 */
	public function countItemsProcessed(){
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
	public function countItemsToProcess(){
		$queueRepository = new tx_crawler_domain_queue_repository();
		return $queueRepository->countNonExecutedItemsByProcess($this);
	}
	
	/**
	 * Returns the Progress of a crawling process as a percentage value
	 *
	 * @return float
	 */
	public function getProgress(){
		$toProcess =$this->countItemsToProcess();
		$processed = $this->countItemsProcessed();
		$all = $toProcess + $processed;
		return round(($all / 100) * $this->countItemsProcessed());
	}
	
	public function getState(){
		if($this->getActive() && $this->getProgress() < 100){
			$stage = 'running';
		}elseif(!$this->getActive() && $this->getProgress() < 100){
			$stage = 'canceled';
		}elseif(!$this->getActive() && $this->getProgress() >= 100){
			$stage = 'completed';
		}
		
		return $stage;
	}
}
?>