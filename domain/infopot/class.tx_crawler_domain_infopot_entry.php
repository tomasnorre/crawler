<?php

class tx_crawler_domain_infopot_entry extends tx_mvc_ddd_abstractDbObject {

	/**
	 * Initialisize the database object with
	 * the table name of current object
	 *
	 * @access     public
	 * @return     string
	 */
	public static function getTableName() {
		return 'tx_crawler_infopot';
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/infopot/class.tx_crawler_domain_infopot_entry.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/infopot/class.tx_crawler_domain_infopot_entry.php']);
}

?>