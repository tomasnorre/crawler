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
 * Class tx_crawler_scheduler_im
 *
 * @package AOE\Crawler\Task
 */
class tx_crawler_scheduler_im extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Define the current mode to process the crawler
	 *
	 * @var string
	 */
	const MODE = 'queue';

	/**
	 * Depth to run the crawler
	 *
	 * @var integer
	 */
	public $depth;

	/**
	 * Configuration to run (comma seperated list)
	 *
	 * @var string
	 */
	public $configuration;

	/**
	 * Function executed from the Scheduler.
	 *
	 * @return bool
	 */
	public function execute() {
		$this->setCliArguments();

			/* @var $crawlerObj tx_crawler_lib */
		$crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
		$crawlerObj->CLI_main_im();
		return TRUE;
	}

	/**
	 * Simulate cli call with setting the required options to the $_SERVER['argv']
	 *
	 * @access protected
	 * @return void
	 */
	protected function setCliArguments() {
			// Make it backwards compatible.
		if (is_null($this->startPage)) {
			$this->startPage = 0;
		}

		$_SERVER['argv'] = array($_SERVER['argv'][0], $this->startPage,'-ss', '-d', $this->depth, '-o', self::MODE, '-conf', implode(',', $this->configuration));
	}


	/**
	 * Retrieve some details about current scheduler task
	 * to make the list view more useful.
	 *
	 * @return string
	 */
	public function getAdditionalInformation() {
			// Make it backwards compatible.
		if (is_null($this->startPage)) {
			$this->startPage = 0;
		}

		return implode(',', $this->configuration) . ' (depth: ' . $this->depth . ', startPage:' . $this->startPage . ')';
	}
}
