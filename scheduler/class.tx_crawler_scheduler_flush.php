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

/**
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@aoemedia.de>
 * @package
 * @version $Id:$
 */
class tx_crawler_scheduler_flush extends tx_scheduler_Task {

	/**
	 * @var	string		$mode
	 */
	public $mode = 'all';

	/**
	 * Function executed from the Scheduler.
	 *
	 * @return	void
	 */
	public function execute() {
		$_SERVER['argv'] = array($_SERVER['argv'][0], 'tx_crawler_cli_flush','0' , '-o', $this->mode);
			/* @var $crawlerObj tx_crawler_lib */
		$crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
		return $crawlerObj->CLI_main_flush();
	}
}

?>