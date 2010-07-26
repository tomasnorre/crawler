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

require_once t3lib_extMgm::extPath('crawler') . 'domain/process/class.tx_crawler_domain_process.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/process/class.tx_crawler_domain_process_collection.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_repository.php';


class tx_crawler_domain_process_repository extends tx_crawler_domain_lib_abstract_repository {

	/**
	 * @var string object class name
	 */
	protected $objectClassname = 'tx_crawler_domain_process';

	protected $tableName = 'tx_crawler_process';

	/**
	 * This method is used to find all cli processes within a limit
	 *
	 * @param int $offset
	 * @param int $limit
	 * @param string $where
	 * @param string $orderby
	 * @return tx_crawler_domain_process_collection a collection of process objects
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 */
	public function findAll($orderField = '',$orderDirection = 'DESC', $itemcount = NULL, $offset = NULL, $where = '') {

		$limit = self::getLimitFromItemCountAndOffset($itemcount,$offset);
		$db = $this->getDB();

		$orderby 	= htmlspecialchars($orderField).' '.htmlspecialchars($orderDirection);
		$groupby 	= '';

		$rows = $db->exec_SELECTgetRows('*',$this->tableName,$where,$groupby,$orderby,$limit);
		$processes = array();

		if(is_array($rows)) {

			foreach($rows as $row) {
				$process = new tx_crawler_domain_process($row);
				$processes[] = $process;
			}
		}

		return new tx_crawler_domain_process_collection($processes);
	}

	/**
	 * This method is used to count all processes in the process table
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @return int
	 */
	public function countAll($where = '1=1') {
		return $this->countByWhere($where);
	}

	/**
	 * Returns the number of active processes
	 * @return int
	 */
	public function countActive() {
		return $this->countByWhere('active=1 AND deleted=0');
	}
	
	/**
	 * Returns the number of processes that live longer than the given timestamp
	 * @return int
	 */
	public function countNotTimeouted($ttl) {
		return $this->countByWhere('deleted=0 AND ttl>'.intval($ttl));
	}

	/**
	 * Get limit clause
	 *
	 * @param int item count
	 * @param int offset
	 * @return string limit clause
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 */
	public static function getLimitFromItemCountAndOffset($itemcount, $offset) {
		$limit = '';
		if (!empty($offset)) {
			$limit .= intval($offset).',';
		}
		if (!empty($itemcount)) {
			$limit .= intval($itemcount);
		}
		return $limit;
	}
}

?>