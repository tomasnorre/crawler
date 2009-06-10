<?php
require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_dbobject.php';

class tx_crawler_domain_queue_entry extends tx_crawler_domain_lib_abstract_dbobject{
	
	protected static $tableName = 'tx_crawler_queue';

	public static function getTableName(){
		return self::$tableName;
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