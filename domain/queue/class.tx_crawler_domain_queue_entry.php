<?php
class tx_crawler_domain_queue_entry{

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
	 * Returns the execution time of the record as int value
	 *
	 * @param void
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @return int
	 */
	public function getExecutionTime(){
		return $this->row['exec_time'];
	}
	
	

}
?>