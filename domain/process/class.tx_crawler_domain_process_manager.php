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
 * Manages cralwer processes and can be used to start a new process or multiple processes
 *
 */
class tx_crawler_domain_process_manager  {
	
	/**
	 * the constructor
	 */
	public function __construct() {
		$this->processRepository	= new tx_crawler_domain_process_repository();
		$this->queueRepository	= new tx_crawler_domain_queue_repository();
		$this->crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
	}
	
	/**
	 * starts multiple processes 
	 * 
	 * @param integer $timeout
	 * @param boolean $verbose
	 */
	public function multiProcess( $timeout, $verbose=TRUE ) {
		$this->crawlerObj->extensionSettings['processLimit'];
		$pendingItemsStart = $this->queueRepository->countAllPendingItems();
		$itemReportLimit = 20;
		$reportItemCount = 	$pendingItemsStart -  $itemReportLimit;
		if ($verbose) {
			$this->reportItemStatus();
		}
		$this->startRequiredProcesses();
		for ($i=0; $i<$timeout; $i++) {
			$currentPendingItems = $this->queueRepository->countAllPendingItems();
			$this->startRequiredProcesses($verbose);
			if ($currentPendingItems == 0) {
				echo 'Finished...'.chr(10);
				break;
			}
			if ($currentPendingItems < $reportItemCount) {
				if ($verbose) {
					$this->reportItemStatus();
				}
				$reportItemCount = $currentPendingItems -  $itemReportLimit;
			}
			sleep(1);
		}		
		if ($currentPendingItems > 0 && $verbose) {
			echo 'Stop with timeout'.chr(10);
		}
	}
	
	/**
	 * Reports curent Status of queue
	 */
	protected function reportItemStatus() {
		echo 'Pending:'.$this->queueRepository->countAllPendingItems().' / Assigned:'.$this->queueRepository->countAllAssignedPendingItems().chr(10);
	}
	
	/**
	 * according to the given count of pending items and the countInARun Setting this method
	 * starts more crawling processes
	 */
	private function startRequiredProcesses($verbose=TRUE) {
		$countInARun = intval($this->crawlerObj->extensionSettings['countInARun']);
		$processLimit = intval($this->crawlerObj->extensionSettings['processLimit']);
		$currentProcesses= $this->processRepository->countActive();
		$availableProcessesCount = $processLimit-$currentProcesses;
		$requiredProcessesCount = ceil($this->queueRepository->countAllUnassignedPendingItems() / $countInARun);
		$startProcessCount = min(array($availableProcessesCount,$requiredProcessesCount));
		if ($startProcessCount <= 0) {
			return;
		}		
		if ($startProcessCount && $verbose) {
			echo 'Start '.$startProcessCount.' new processes (Running:'.$currentProcesses.')';
		}
		for($i=0;$i<$startProcessCount;$i++) {
			usleep(100);
			if ($this->startProcess()) {
				if ($verbose) {
					echo '.';
				}
			}		
		}
		if ($verbose) {
			echo chr(10);
		}
	}
		
	/**
	 * starts new process
	 * @throws Exception if no crawlerprocess was started
	 */
	public function startProcess() {
		$ttl = (time() + intval($this->crawlerObj->extensionSettings['processMaxRunTime'])-1);
		$current = $this->processRepository->countNotTimeouted($ttl);
		$completePath = '(' .escapeshellcmd($this->getCrawlerCliPath()) . ' &) > /dev/null';
		if (system($completePath) === FALSE) {
			throw new Exception('could not start process!');
		}
		else {
			for ($i=0;$i<10;$i++) {
				if ($this->processRepository->countNotTimeouted($ttl) > $current) {
					return true;
				}
				sleep(1);
			}
			throw new Exception('Something went wrong: process did not appear within 10 seconds.');
		}		
	}

	/**
	 * Returns the path to start the crawler from the command line
	 *
	 * @return string
	 */
	public function getCrawlerCliPath(){
		$phpPath 		= $this->crawlerObj->extensionSettings['phpPath'] . ' ';
		$pathToTypo3 	= rtrim(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT'), '/');
		$pathToTypo3 	.= rtrim(t3lib_div::getIndpEnv('TYPO3_SITE_PATH'), '/');
		$cliPart	 	= '/typo3/cli_dispatch.phpsh crawler';
		return $phpPath.$pathToTypo3.$cliPart;
	}
	
}
