<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Class tx_crawler_scheduler_crawl
 *
 * @package AOE\Crawler\Task
 */
class tx_crawler_scheduler_crawl extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * @var integer
	 */
	public $sleepTime;

	/**
	 * @var integer
	 */
	public $sleepAfterFinish;

	/**
	 * @var integer
	 */
	public $countInARun;

	/**
	 * Function executed from the Scheduler.
	 *
	 * @return bool
	 */
	public function execute() {
		$this->setCliArguments();

			/* @var $crawlerObj tx_crawler_lib */
		$crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
		$crawlerObj->CLI_main();
		return TRUE;
	}

	/**
	 * Simulate cli call with setting the required options to the $_SERVER['argv']
	 *
	 * @access protected
	 * @return void
	 */
	protected function setCliArguments() {
		$_SERVER['argv'] = array($_SERVER['argv'][0], '0', '-ss', '--sleepTime', $this->sleepTime, '--sleepAfterFinish', $this->sleepAfterFinish, '--countInARun', $this->countInARun);
	}
}
