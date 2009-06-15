<?php

require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_repository.php';

class tx_crawler_domain_reason_repository extends tx_crawler_domain_lib_abstract_repository{

	protected $objectClassname = 'tx_crawler_domain_reason';

	public function add(tx_crawler_domain_reason $reason){
		$row = $reason->getRow();

		unset($row['uid']);

		$result 	= $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->tableName, $row);
		$uid 		= $GLOBALS['TYPO3_DB']->sql_insert_id();
		$reason->setUid($uid);

		return $result;
	}
}
?>