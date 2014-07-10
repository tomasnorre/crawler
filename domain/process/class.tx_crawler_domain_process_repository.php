<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 AOE GmbH (dev@aoe.com)
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

class tx_crawler_domain_process_repository extends tx_crawler_domain_lib_abstract_repository {

	/**
	 * @var string object class name
	 */
	protected $objectClassname = 'tx_crawler_domain_process';

	/**
	 * @var string
	 */
	protected $tableName = 'tx_crawler_process';

	/**
	 * This method is used to find all cli processes within a limit.
	 * 
	 * @param  string  $orderField
	 * @param  string  $orderDirection
	 * @param  integer $itemCount
	 * @param  integer $offset
	 * @param  string  $where
	 * @return tx_crawler_domain_process_collection
	 */
	public function findAll($orderField = '', $orderDirection = 'DESC', $itemCount = NULL, $offset = NULL, $where = '') {
		/** @var tx_crawler_domain_process_collection $collection */
		$collection = t3lib_div::makeInstance('tx_crawler_domain_process_collection');

		$orderField = trim($orderField);
		$orderField = empty($orderField) ? 'process_id' : $orderField;

		$orderDirection = strtoupper(trim($orderDirection));
		$orderDirection = in_array($orderDirection, array('ASC', 'DESC')) ? $orderDirection : 'DESC';

		$rows = $this->getDB()->exec_SELECTgetRows(
			'*',
			$this->tableName,
			$where,
			'',
			htmlspecialchars($orderField) . ' ' . htmlspecialchars($orderDirection),
			self::getLimitFromItemCountAndOffset($itemCount, $offset)
		);

		if (is_array($rows)) {
			foreach($rows as $row) {
				$collection->append(t3lib_div::makeInstance($this->objectClassname, $row));
			}
		}

		return $collection;
	}

	/**
	 * This method is used to count all processes in the process table.
	 *
	 * @param  string $where    Where clause
	 * @return integer
	 * @author Timo Schmidt <timo.schmidt@aoe.com>
	 */
	public function countAll($where = '1 = 1') {
		return $this->countByWhere($where);
	}

	/**
	 * Returns the number of active processes.
	 *
	 * @return integer
	 */
	public function countActive() {
		return $this->countByWhere('active = 1 AND deleted = 0');
	}

	/**
	 * Returns the number of processes that live longer than the given timestamp.
	 *
	 * @param  integer $ttl
	 * @return integer
	 */
	public function countNotTimeouted($ttl) {
		return $this->countByWhere('deleted = 0 AND ttl > ' . intval($ttl));
	}

	/**
	 * Get limit clause
	 *
	 * @param  integer $itemCount   Item count
	 * @param  integer $offset      Offset
	 * @return string               Limit clause
	 * @author Fabrizio Branca <fabrizio.branca@aoem.com>
	 */
	public static function getLimitFromItemCountAndOffset($itemCount, $offset) {
		$itemCount = filter_var($itemCount, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'default' => 20)));
		$offset = filter_var($offset, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'default' => 0)));
		$limit = $offset . ', ' . $itemCount;

		return $limit;
	}
}
