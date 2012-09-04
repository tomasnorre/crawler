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
 * This api class can be used to add pages to the crawlerqueue.
 * It uses internally the class tx_crawler_lib modify the queue.
 */

require_once t3lib_extMgm::extPath('crawler').'system/class.tx_crawler_system_validator.php';
require_once t3lib_extMgm::extPath('crawler').'domain/queue/class.tx_crawler_domain_queue_repository.php';
require_once t3lib_extMgm::extPath('crawler').'domain/process/class.tx_crawler_domain_process_repository.php';


class tx_crawler_api {

	/**
	 * @var tx_crawler_lib
	 */
	private $crawlerObj;

	/**
	 * @var tx_crawler_system_validator validator
	 */
	private $validator;

	/**
	 * @var tx_crawler_domain_queue_repository queue repository
	 */
	protected $queueRepository;
	
	/**
	 * @var $allowedConfigrations array
	 */
	protected $allowedConfigrations = array();

	/**
	 * Returns an instance of the validator
	 *
	 * @return tx_crawler_system_validator
	 */
	protected function findValidator() {
		if(!$this->validator instanceof tx_crawler_system_validator) {
			$this->validator = t3lib_div::makeInstance('tx_crawler_system_validator');
		}

		return $this->validator;
	}

	/**
	 * Each crawlerrun has a setid, this facade method delegates
	 * the it to the crawler object
	 *
	 * @param int
	 */
	public function overwriteSetId($id) {
		$this->findCrawler()->setID = intval($id);
	}
	
	/**
	 * This method is used to limit the configuration selection to
	 * a set of configurations. 
	 * 
	 * @param array $allowedConfigurations
	 */
	public function setAllowedConfigurations(array $allowedConfigurations){
		$this->allowedConfigrations = $allowedConfigurations;
	}

	/**
	 * Returns the setID of the crawler
	 *
	 * @return int
	 */
	public function getSetId() {
		return $this->findCrawler()->setID;
	}

	/**
	 * Method to get an instance of the internal crawler singleton
	 *
	 * @return tx_crawler_lib Instance of the crawler lib
	 */
	protected function findCrawler() {
		if(!is_object($this->crawlerObj)) {
			$this->crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
			$this->crawlerObj->setID = t3lib_div::md5int(microtime());
		}

		if (is_object($this->crawlerObj)) {
			return $this->crawlerObj;
		} else {
			throw new Exception("no crawler object");
		}
	}

	/**
	 * Adds a page to the crawlerqueue by uid
	 *
	 * @param int uid
	 */
	public function addPageToQueue($uid) {
		$uid = intval($uid);
		//non timed elements will be added with timestamp 0
		$this->addPageToQueueTimed($uid,0);
	}

	/**
	 * This method is used to limit the processing instructions to the processing instructions 
	 * that are allowed.
	 * 
	 * @return array
	 */
	protected function filterUnallowedConfigurations($configurations) {
		if(count($this->allowedConfigrations) > 0){
			// 	remove configuration that does not match the current selection
			foreach ($configurations as $confKey => $confArray) {
				if (!in_array($confKey, $this->allowedConfigrations)) {
					unset($configurations[$confKey]);
				}
			}
		}		
		
		return $configurations;
	}
	
	/**
	 * Adds a page to the crawlerqueue by uid and sets a
	 * timestamp when the page should be crawled.
	 *
	 * @param int pageid
	 * @param int timestamp
	 */
	public function addPageToQueueTimed($uid,$time) {

		$uid 		= intval($uid);
		$time 		= intval($time);

		$crawler 			= $this->findCrawler();
		$pageData 			= t3lib_div::makeInstance('t3lib_pageSelect')->getPage($uid);
		$configurations 	= $crawler->getUrlsForPageRow($pageData);
		$configurations 	= $this->filterUnallowedConfigurations($configurations);
		$downloadUrls		= array();
		$duplicateTrack		= array();
		
		if (is_array($configurations)) {
			foreach ($configurations as  $cv) {
				//enable inserting of entries
				$crawler->registerQueueEntriesInternallyOnly=false;
				$crawler->urlListFromUrlArray(
					$cv,
					$pageData,
					$time,
					300,
					true,
					false,
					$duplicateTrack,
					$downloadUrls,
					array_keys($this->getCrawlerProcInstructions())
				);

				//reset the queue because the entries have been written to the db
				unset($crawler->queueEntries);
			}
		} else {
			//no configuration found
		}
	}

	/**
	 * Counts all entrys in the database which are scheduled for a given page id and a schedule timestamp.
	 *
	 * @param int $page_uid
	 * @param int $timestamp
	 * @return int
	 */
	protected function countEntriesInQueueForPageByScheduletime($page_uid,$schedule_timestamp) {
		//if the same page is scheduled for the same time and has not be executed?
		if($schedule_timestamp == 0) {
			//untimed elements need an exec_time with 0 because they can occure multiple times
			$where = 'page_id='.$page_uid.' AND exec_time = 0 AND scheduled='.$schedule_timestamp;
		} else {
			//timed elementes have got a fixed schedule time, if a record with this time
			//exists it is maybe queued for the future, or is has been queue for the past and therefore
			//also been processed.
			$where = 'page_id='.$page_uid.' AND scheduled='.$schedule_timestamp;
		}

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*) as cnt','tx_crawler_queue',$where));

		return intval($row['cnt']);
	}

	/**
	 * Determines if a page is queued.
	 *
	 * @param int uid
	 * @param boolean configure the method, to handle only unprocessed items as queued
	 */
	public function isPageInQueue($uid,$unprocessed_only = true,$timed_only=false,$timestamp=false) {
		$uid = intval($uid);

		if($timestamp != false) { $timestamp = intval($timestamp);}

		if(!$this->findValidator()->isInt($uid)) { die('wrong parameter type'); }

		$query = 'count(*) as anz';
		$where = 'page_id = '.$uid;

		if($unprocessed_only) {
			$where .= ' AND exec_time = 0';
		}
		if($timed_only) {
			$where .= ' AND scheduled != 0';
		}
		if($timestamp) {
			$where .= ' AND scheduled = '.$timestamp;
		}

		$rs 	= $GLOBALS['TYPO3_DB']->exec_SELECTquery($query,'tx_crawler_queue',$where);
		$row 	= $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rs);
		$entriesInDB = intval($row['anz']);

		$is_queued = $entriesInDB > 0;

		return $is_queued;
	}

	/**
	 * Method to return the latest Crawle Timestamp for a page.
	 *
	 * @param uid id of the page
	 */
	public function getLatestCrawlTimestampForPage($uid,$future_crawldates_only=false, $unprocessed_only = false) {
		$uid = intval($uid);
		$query = 'max(scheduled) as latest';
		$where = ' page_id = '.$uid;

		if($future_crawldates_only) {
			$where .= ' AND scheduled > '.time();
		}

		if($unprocessed_only) {
			$where .= ' AND exec_time = 0';
		}

		$rs 	= $GLOBALS['TYPO3_DB']->exec_SELECTquery($query,'tx_crawler_queue',$where);
		if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rs)) {
			$res = $row['latest'];
		} else {
			$res = 0;
		}

		return $res;
	}

	/**
	 * Returns an array with timestamps when the page has been scheduled for crawling and
	 * at what time the scheduled crawl has been executed. The array also contains items that are
	 * scheduled but have note been crawled yet.
	 *
	 * @param int uid of the page
	 * @return array array with the crawlhistory of a page => 0 : scheduled time , 1 : execuded_time, 2 : set_id
	 */
	public function getCrawlHistoryForPage($uid,$limit=false) {
		$uid = intval($uid);
		$limit = mysql_real_escape_string($limit);

		$query = 'scheduled, exec_time, set_id';
		$where = ' page_id = '.$uid;

		$limit_query = ($limit) ? $limit : null;

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($query, 'tx_crawler_queue', $where, null, null, $limit_query);

		return $rows;
	}

	/**
	 * Method to determine unprocessed Items in the crawler queue.
	 *
	 * @return array
	 */
	public function getUnprocessedItems() {
		$query 	= '*';
		$where 	= 'exec_time = 0';
		$rows 	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($query,'tx_crawler_queue',$where,'','page_id, scheduled');

		return $rows;
	}

	/**
	 * Method to get the number of unprocessed items in the crawler
	 *
	 * @param int number of unprocessed items in the queue
	 */
	public function countUnprocessedItems() {
		$query = 'count(page_id) as anz';
		$where = 'exec_time = 0';
		$rs = 	$GLOBALS['TYPO3_DB']->exec_SELECTquery($query,'tx_crawler_queue',$where);
		$row = 	$GLOBALS['TYPO3_DB']->sql_fetch_assoc($rs);

		return $row['anz'];
	}

	/**
	 * Method to check if a page is in the queue which is timed for a
	 * date when it should be crawled
	 *
	 * @param int uid of the page
	 * @param boolean only respect unprocessed pages
	 */
	public function isPageInQueueTimed($uid,$show_unprocessed = true) {
		$uid = intval($uid);
		return $this->isPageInQueue($uid,$show_unprocessed,show_timed_only);
	}

	/**
	 * Reads the registered processingInstructions of the crawler
	 *
	 * @return unknown
	 */
	private function getCrawlerProcInstructions() {
		return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'];
	}

	/**
	 * Removes an queue entry with a given queue id
	 *
	 * @param int $qid
	 */
	public function removeQueueEntrie($qid) {
		$qid = intval($qid);
		$table = 'tx_crawler_queue';
		$where = ' qid='.$qid;
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($table,$where);
	}

	/**
	 * Get queue statistics
	 *
	 * @param void
	 * @return array array('assignedButUnprocessed' => <>, 'unprocessed' => <>);
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-02
	 */
	public function getQueueStatistics() {
		return array(
			'assignedButUnprocessed' => $this->getQueueRepository()->countAllAssignedPendingItems(),
			'unprocessed' => $this->getQueueRepository()->countAllPendingItems()
		);
	}

	/**
	 * Get queue repository
	 *
	 * @param void
	 * @return tx_crawler_domain_queue_repository queue repository
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-03
	 */
	protected function getQueueRepository() {
		if (!$this->queueRepository instanceof tx_crawler_domain_queue_repository) {
			$this->queueRepository = new tx_crawler_domain_queue_repository();
		}
		return $this->queueRepository;
	}

	/**
	 * Get queue statistics by configuration
	 *
	 * @param void
	 * @return array array of array('configuration' => <>, 'assignedButUnprocessed' => <>, 'unprocessed' => <>)
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-02
	 */
	public function getQueueStatisticsByConfiguration() {
		$statistics = $this->getQueueRepository()->countPendingItemsGroupedByConfigurationKey();

		$setIds = $this->getQueueRepository()->getSetIdWithUnprocessedEntries();

		$totals = $this->getQueueRepository()->getTotalQueueEntriesByConfiguration($setIds);

		// "merge" arrays
		foreach ($statistics as $key => &$value) {
			$value['total'] = $totals[$value['configuration']];
		}

		return $statistics;
	}

	/**
	 * Get active processes count
	 *
	 * @param void
	 * @return int
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-03
	 */
	public function getActiveProcessesCount() {
		$processRepository = new tx_crawler_domain_process_repository();
		return $processRepository->countActive();
	}
	
	/**
	 * Get last processed entries
	 *
	 * @param int limit
	 * @return array
	 */
	public function getLastProcessedQueueEntries($limit) {
		return $this->getQueueRepository()->getLastProcessedEntries('*', $limit);
	}

	/**
	 * Get current crawling speed
	 *
	 * @param float|false page speed in pages per minute
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-03
	 */
	public function getCurrentCrawlingSpeed() {
		$lastProcessedEntries = $this->getQueueRepository()->getLastProcessedEntriesTimestamps();

		if (count($lastProcessedEntries) < 10) {
			// not enough information
			return false;
		}

		$tooOldDelta = 60; // time between two entries is "too old"

		$compareValue = time();
		$startTime = $lastProcessedEntries[0];

		$pages = 0;

		reset($lastProcessedEntries);
		while (list($key, $timestamp) = each($lastProcessedEntries)) {
			if ($compareValue - $timestamp > $tooOldDelta) break;
			$compareValue = $timestamp;
			$pages++;
		}

		if ($pages < 10) {
			// not enough information
			return false;
		}
		$oldestTimestampThatIsNotTooOld = $compareValue;
		$time = $startTime - $oldestTimestampThatIsNotTooOld;
		$speed = $pages / ($time / 60);

		return $speed;
	}

	/**
	 * Get some performance data
	 *
	 * @param integer $start
	 * @param integer $end
	 * @param integer $resolution
	 * @return array data
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-09-08
	 */
	public function getPerformanceData($start, $end, $resolution) {
		$data = array();

		$data['urlcount'] = 0;
		$data['start'] = $start;
		$data['end'] = $end;
		$data['duration'] =	$data['end'] - $data['start'];

		if ($data['duration'] < 1) {
			throw new Exception('End timestamp must be after start timestamp');
		}

		for ($slotStart = $start; $slotStart < $end; $slotStart += $resolution) {
			$slotEnd = min($slotStart+$resolution-1, $end);
			$slotData = $this->getQueueRepository()->getPerformanceData($slotStart, $slotEnd);

			$slotUrlCount = 0;
			foreach($slotData as $processId => &$processData) {
				$duration = $processData['end'] - $processData['start'];
				if ($processData['urlcount'] > 5 && $duration > 0) {
					$processData['speed'] = 60 * 1 / ($duration/$processData['urlcount']);
				}
				$slotUrlCount += $processData['urlcount'];
			}

			$data['urlcount'] += $slotUrlCount;

			$data['slots'][$slotEnd] = array(
				'amountProcesses' => count($slotData),
				'urlcount' => $slotUrlCount,
				'processes' => $slotData,
			);

			if ($slotUrlCount > 5) {
				$data['slots'][$slotEnd]['speed'] = 60 * 1 / ($slotEnd-$slotStart / $slotUrlCount);
			} else {
				$data['slots'][$slotEnd]['speed'] = 0;
			}

		}

		if ($data['urlcount'] > 5) {
			$data['speed'] = 60 * 1 / ($data['duration'] / $data['urlcount']);
		} else {
			$data['speed'] = 0;
		}

		return $data;
	}

	/**
	 * Wrapper to support old and new method to test integer values.
	 *
	 * @param integer $value
	 * @return integer
	 */
	static public function convertToPositiveInteger($value) {
		if (version_compare(TYPO3_version, '4.6.0', '>=')) {
			$result = t3lib_utility_Math::convertToPositiveInteger($value);
		} else {
			$result = t3lib_div::intval_positive($value);
		}

		return $result;
	}


	/**
	 * Wrapper to support old an new method to test integer value.
	 *
	 * @param integer $value
	 * @param integer $min
	 * @param integer $max
	 * @param integer $default
	 * @return integer
	 */
	static public function forceIntegerInRange($value, $min, $max = 2000000, $default = 0) {
		if (version_compare(TYPO3_version, '4.6.0', '>=')) {
			$result = t3lib_utility_Math::forceIntegerInRange($value, $min, $max, $default);
		} else {
			$result = t3lib_div::intInRange($value, $min, $max, $default);
		}
		return $result;
	}

	/**
	 * Wrapper to support old an new method to test integer value.
	 *
	 * @param integer $value
	 * @return bool
	 */
	static public function canBeInterpretedAsInteger($value) {
		if (version_compare(TYPO3_version, '4.6.0', '>=')) {
			$result = t3lib_utility_Math::canBeInterpretedAsInteger($value);
		} else {
			$result = t3lib_div::testInt($value);
		}
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_api.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_api.php']);
}

?>