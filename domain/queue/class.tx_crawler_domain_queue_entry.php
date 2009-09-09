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

require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_dbobject.php';

class tx_crawler_domain_queue_entry extends tx_crawler_domain_lib_abstract_dbobject {

	/**
	 * @var string table name
	 */
	protected static $tableName = 'tx_crawler_queue';

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