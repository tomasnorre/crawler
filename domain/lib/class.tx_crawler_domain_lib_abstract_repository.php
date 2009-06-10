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
}

?>