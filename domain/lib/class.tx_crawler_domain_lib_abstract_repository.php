<?php
abstract class tx_crawler_domain_lib_abstract_repository{
	protected $objectClassname;
	
	protected $tableName;
	/**
	 * Contructor to initialize the repository
	 *
	 */
	public function __construct(){
		
		$className = $this->objectClassname;
		$this->tableName = call_user_func ( array($className, 'getTableName') );
	}
	
	
	/**
	 * Returns an instance of the TYPO3 database class.
	 *
	 * @return  t3lib_DB
	 */
	protected function getDB(){
		return 	$GLOBALS['TYPO3_DB'];
		
	}
	
	
	/**
	 * Counts items by a given where clause
	 *
	 * @param unknown_type $where
	 * @return unknown
	 */
	protected function countByWhere($where){
		$db 	= $this->getDB();
		$rs 	= $db->exec_SELECTquery('count(*) as anz',$this->tableName,$where);
		$res 	= $db->sql_fetch_assoc($rs);

		return $res['anz']; 
	}
}

?>