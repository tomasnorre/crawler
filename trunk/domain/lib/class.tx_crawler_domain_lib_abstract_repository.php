<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 AOE media (dev@aoemedia.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

abstract class tx_crawler_domain_lib_abstract_repository {

	/**
	 * @var string object class name
	 */
	protected $objectClassname;

	/**
	 * @var string table name
	 */
	protected $tableName;

	/**
	 * Returns an instance of the TYPO3 database class.
	 *
	 * @return  t3lib_DB
	 */
	protected function getDB() {
		return 	$GLOBALS['TYPO3_DB'];
	}

	/**
	 * Counts items by a given where clause
	 *
	 * @param unknown_type $where
	 * @return unknown
	 */
	protected function countByWhere($where) {
		$db 	= $this->getDB();
		$rs 	= $db->exec_SELECTquery('count(*) as anz', $this->tableName, $where);
		$res 	= $db->sql_fetch_assoc($rs);

		return $res['anz'];
	}
}

?>