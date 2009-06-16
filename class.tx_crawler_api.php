<?php
/**
 * This api class can be used to add pages to the crawlerqueue.
 * It uses internally the class tx_crawler_lib modify the queue.
 *
 */
require_once t3lib_extMgm::extPath('crawler') . 'class.tx_crawler_validator.php';

class tx_crawler_api {

	private $crawlerObj;
	private $allow_duplicate_entries = false;
	private $validator;

	public function __construct() {
		$this->validator = t3lib_div::makeInstance('tx_crawler_validator');
	}

	/**
	* Each crawlerrun has a setid, this facade method delegates
	* the it to the crawler object
	*/
	public function overwriteSetId($id) {
		$id = intval($id);
		$this->findCrawler()->setID = $id;
	}

	/**
	* Method to configure the facade to allow duplicate entrys of
	* pages in the crawlerqueue.
	*
	* @param boolean
	*/
	public function setAllowDuplicateEntries($duplicate) {
		$this->allow_duplicate_entries = $duplicate;
	}

	/**
	* Method to get an instance of the internal crawler singleton
	*
	* @return tx_crawler_lib Instance of the crawler lib
	*/
	private function findCrawler() {
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
	public function addPageToQueue($uid, tx_crawler_domain_reason $reason = null) {
		$uid = intval($uid);
		//non timed elements will be added with timestamp 0
		$this->addPageToQueueTimed($uid,0,$reason);
	}

	/**
	 * Adds a page to the crawlerqueue by uid and sets a
	 * timestamp when the page should be crawled.
	 *
	 * @param int pageid
	 * @param int timestamp
	 */
	public function addPageToQueueTimed($uid,$time, tx_crawler_domain_reason $reason = null) {
		$uid = intval($uid);
		$time = intval($time);

		if(!$this->validator->isInt($uid) || !$this->validator->isInt($time)) {
			throw new Exception('wrong parameter type '.var_dump($uid).' '.var_dump($time).' in function '.__METHOD__);
		}

		$crawler 	= $this->findCrawler();
		$conf 		= $crawler->getUrlsForPageId($uid);
		$pageData = t3lib_div::makeInstance('t3lib_pageSelect')->getPage($uid);

		//pTestI ist for testing, to check if queue entries exist
		//pI is for inserting
		$pTestI = $pI = array(array(),array(),array_keys($this->getCrawlerProcInstructions()));

		if (is_array($conf)) {
			foreach ($conf as $ck => $cv) {

				if ($this->allow_duplicate_entries) {
					//if duplicate entries are allowed always insert the items into the queue
					$doCreate = true;
				} else {
					//disable inserting in the crawler_lib to count the number of entries
					$crawler->registerQueueEntriesInternallyOnly=true;

					//$pTestI[0] => duplicateTrack,$pTestI[1] => downloadUrls, $pTestI[2] => incomingProcInstructions
					$crawler->urlListFromUrlArray($cv,$pageData,$time,300,true,false,$pTestI[0],$pTestI[1],$pTestI[2],$reason);
					$entriesInQueue  =  count($crawler->queueEntries);
					unset($crawler->queueEntries);
					$entriesInDB 	= $this->countEntriesInQueueForPageByScheduletime($uid,$time);

					//if the number of unprocessed entries in the database is less than the numbers of entries to process do
					//an insert for pages to the queue
					$doCreate = ($entriesInQueue > $entriesInDB) ? true : false;
				}

				if($doCreate) {
					//enable inserting of entries
					$crawler->registerQueueEntriesInternallyOnly=false;
					$crawler->urlListFromUrlArray($cv,$pageData,$time,300,true,false,$pI[0],$pI[1],$pI[2],$reason);
					//reset the queue because the entries have been written to the db
					unset($crawler->queueEntries);

				} else {
					//nothing todo maybe the entries already exist
				}
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
		}else{
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
	public function isPageInQueue($uid,$unprocessed_only = true,$timed_only=false,$timstamp=false) {
		$uid = intval($uid);

		if($timestamp != false) {
			$timestamp = intval($timestamp);
		}

		if(!$this->validator->isInt($uid)) { die('wrong parameter type'); }

		$query = 'count(*) as anz';
		$where = 'page_id = '.$uid;

		if($unprocessed_only) {
			$where .= ' AND exec_time = 0';
		}
		if($timed_only) {
			$where .= ' AND scheduled != 0';
		}
		if($timstamp) {
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
		}else{
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


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_api.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_api.php']);
}
?>