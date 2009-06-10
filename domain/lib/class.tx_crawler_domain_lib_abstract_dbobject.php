<?php
abstract class tx_crawler_domain_lib_abstract_dbobject{
	/**
	 * @var array
	 */	
	protected $row;
	
	protected static $tableName;
	
	/**
	 * Constructor
	 *
	 * @param array $row optional array with propertys
	 */
	public function __construct($row = array()){
		$this->row = $row;
	}
	
	abstract public static function getTableName();
	
	
	/**
	 * Returns the propertys of the object as array
	 *
	 * @return array
	 */
	public function getRow(){
		return $this->row;
	}
	
	
}
?>