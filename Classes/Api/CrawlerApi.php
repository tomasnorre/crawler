<?php
namespace AOE\Crawler\Api;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class CrawlerApi
 *
 * @package AOE\Crawler\Api
 */
class CrawlerApi
{
    /**
     * @var CrawlerController
     */
    private $crawlerController;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var $allowedConfigurations array
     */
    protected $allowedConfigurations = [];

    /**
     * Each crawler run has a setid, this facade method delegates
     * the it to the crawler object
     *
     * @param int
     */
    public function overwriteSetId($id)
    {
        $this->findCrawler()->setID = intval($id);
    }

    /**
     * This method is used to limit the configuration selection to
     * a set of configurations.
     *
     * @param array $allowedConfigurations
     */
    public function setAllowedConfigurations(array $allowedConfigurations)
    {
        $this->allowedConfigurations = $allowedConfigurations;
    }

    /**
     * @return array
     */
    public function getAllowedConfigurations()
    {
        return $this->allowedConfigurations;
    }

    /**
     * Returns the setID of the crawler
     *
     * @return int
     */
    public function getSetId()
    {
        return $this->findCrawler()->setID;
    }

    /**
     * Method to get an instance of the internal crawler singleton
     *
     * @return CrawlerController Instance of the crawler lib
     *
     * @throws \Exception
     */
    protected function findCrawler()
    {
        if (!is_object($this->crawlerController)) {
            $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
            $this->crawlerController->setID = GeneralUtility::md5int(microtime());
        }

        if (is_object($this->crawlerController)) {
            return $this->crawlerController;
        } else {
            throw new \Exception('no crawler object', 1512659759);
        }
    }

    /**
     * Adds a page to the crawlerqueue by uid
     *
     * @param int $uid uid
     */
    public function addPageToQueue($uid)
    {
        $uid = intval($uid);
        //non timed elements will be added with timestamp 0
        $this->addPageToQueueTimed($uid, 0);
    }

    /**
     * This method is used to limit the processing instructions to the processing instructions
     * that are allowed.
     *
     * @return array
     */
    protected function filterUnallowedConfigurations($configurations)
    {
        if (count($this->allowedConfigurations) > 0) {
            // 	remove configuration that does not match the current selection
            foreach ($configurations as $confKey => $confArray) {
                if (!in_array($confKey, $this->allowedConfigurations)) {
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
     * @param int $uid pageid
     * @param int $time timestamp
     */
    public function addPageToQueueTimed($uid, $time)
    {
        $uid = intval($uid);
        $time = intval($time);

        $crawler = $this->findCrawler();
        $pageData = GeneralUtility::makeInstance(PageRepository::class)->getPage($uid);
        $configurations = $crawler->getUrlsForPageRow($pageData);
        $configurations = $this->filterUnallowedConfigurations($configurations);
        $downloadUrls = [];
        $duplicateTrack = [];

        if (is_array($configurations)) {
            foreach ($configurations as $cv) {
                //enable inserting of entries
                $crawler->registerQueueEntriesInternallyOnly = false;
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
     * @param int $schedule_timestamp
     *
     * @return int
     */
    protected function countEntriesInQueueForPageByScheduleTime($page_uid, $schedule_timestamp)
    {
        //if the same page is scheduled for the same time and has not be executed?
        if ($schedule_timestamp == 0) {
            //un-timed elements need an exec_time with 0 because they can occur multiple times
            $where = 'page_id=' . $page_uid . ' AND exec_time = 0 AND scheduled=' . $schedule_timestamp;
        } else {
            //timed elements have got a fixed schedule time, if a record with this time
            //exists it is maybe queued for the future, or is has been queue for the past and therefore
            //also been processed.
            $where = 'page_id=' . $page_uid . ' AND scheduled=' . $schedule_timestamp;
        }

        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'count(*) as cnt',
            'tx_crawler_queue',
            $where
        ));

        return intval($row['cnt']);
    }

    /**
     * Determines if a page is queued
     *
     * @param $uid
     * @param bool $unprocessed_only
     * @param bool $timed_only
     * @param bool $timestamp
     *
     * @return bool
     */
    public function isPageInQueue($uid, $unprocessed_only = true, $timed_only = false, $timestamp = false)
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            throw new \InvalidArgumentException('Invalid parameter type', 1468931945);
        }

        $isPageInQueue = false;

        $whereClause = 'page_id = ' . (integer)$uid;

        if (false !== $unprocessed_only) {
            $whereClause .= ' AND exec_time = 0';
        }

        if (false !== $timed_only) {
            $whereClause .= ' AND scheduled != 0';
        }

        if (false !== $timestamp) {
            $whereClause .= ' AND scheduled = ' . (integer)$timestamp;
        }

        $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
            '*',
            'tx_crawler_queue',
            $whereClause
        );

        if (false !== $count && $count > 0) {
            $isPageInQueue = true;
        }

        return $isPageInQueue;
    }

    /**
     * Method to return the latest Crawle Timestamp for a page.
     *
     * @param int $uid uid id of the page
     * @param bool $future_crawldates_only
     * @param bool $unprocessed_only
     *
     * @return int
     */
    public function getLatestCrawlTimestampForPage($uid, $future_crawldates_only = false, $unprocessed_only = false)
    {
        $uid = intval($uid);
        $query = 'max(scheduled) as latest';
        $where = ' page_id = ' . $uid;

        if ($future_crawldates_only) {
            $where .= ' AND scheduled > ' . time();
        }

        if ($unprocessed_only) {
            $where .= ' AND exec_time = 0';
        }

        $rs = $GLOBALS['TYPO3_DB']->exec_SELECTquery($query, 'tx_crawler_queue', $where);
        if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rs)) {
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
     * @param int $uid uid of the page
     * @param bool $limit
     *
     * @return array array with the crawl-history of a page => 0 : scheduled time , 1 : executed_time, 2 : set_id
     */
    public function getCrawlHistoryForPage($uid, $limit = 0)
    {
        $uid = intval($uid);
        $limit = intval($limit);

        $query = 'scheduled, exec_time, set_id';
        $where = ' page_id = ' . $uid;

        $limit_query = ($limit) ? $limit : null;

        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($query, 'tx_crawler_queue', $where, null, null, $limit_query);
        return $rows;
    }

    /**
     * Method to determine unprocessed Items in the crawler queue.
     *
     * @return array
     */
    public function getUnprocessedItems()
    {
        $query = '*';
        $where = 'exec_time = 0';
        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($query, 'tx_crawler_queue', $where, '', 'page_id, scheduled');

        return $rows;
    }

    /**
     * Method to get the number of unprocessed items in the crawler
     *
     * @param int number of unprocessed items in the queue
     */
    public function countUnprocessedItems()
    {
        $query = 'count(page_id) as anz';
        $where = 'exec_time = 0';
        $rs = $GLOBALS['TYPO3_DB']->exec_SELECTquery($query, 'tx_crawler_queue', $where);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rs);

        return $row['anz'];
    }

    /**
     * Method to check if a page is in the queue which is timed for a
     * date when it should be crawled
     *
     * @param int $uid uid of the page
     * @param boolean $show_unprocessed only respect unprocessed pages
     *
     * @return boolean
     */
    public function isPageInQueueTimed($uid, $show_unprocessed = true)
    {
        $uid = intval($uid);

        return $this->isPageInQueue($uid, $show_unprocessed);
    }

    /**
     * Reads the registered processingInstructions of the crawler
     *
     * @return array
     */
    private function getCrawlerProcInstructions()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'])) {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'];
        }

        return [];
    }

    /**
     * Removes an queue entry with a given queue id
     *
     * @param int $qid
     */
    public function removeQueueEntrie($qid)
    {
        $qid = intval($qid);
        $table = 'tx_crawler_queue';
        $where = ' qid=' . $qid;
        $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);
    }

    /**
     * Get queue statistics
     *
     * @param void
     *
     * @return array array('assignedButUnprocessed' => <>, 'unprocessed' => <>);
     */
    public function getQueueStatistics()
    {
        return [
            'assignedButUnprocessed' => $this->getQueueRepository()->countAllAssignedPendingItems(),
            'unprocessed' => $this->getQueueRepository()->countAllPendingItems()
        ];
    }

    /**
     * Get queue repository
     *
     * @return QueueRepository
     */
    protected function getQueueRepository()
    {
        if (!$this->queueRepository instanceof QueueRepository) {
            $this->queueRepository = new QueueRepository();
        }

        return $this->queueRepository;
    }

    /**
     * Get queue statistics by configuration
     *
     * @return array array of array('configuration' => <>, 'assignedButUnprocessed' => <>, 'unprocessed' => <>)
     */
    public function getQueueStatisticsByConfiguration()
    {
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
     *
     * @return int
     */
    public function getActiveProcessesCount()
    {
        $processRepository = new ProcessRepository();

        return $processRepository->countActive();
    }

    /**
     * Get last processed entries
     *
     * @param int $limit
     *
     * @return array
     */
    public function getLastProcessedQueueEntries($limit)
    {
        return $this->getQueueRepository()->getLastProcessedEntries('*', $limit);
    }

    /**
     * Get current crawling speed
     *
     * @param float|false page speed in pages per minute
     *
     * @return int
     */
    public function getCurrentCrawlingSpeed()
    {
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
            if ($compareValue - $timestamp > $tooOldDelta) {
                break;
            }
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
     *
     * @return array data
     *
     * @throws \Exception
     */
    public function getPerformanceData($start, $end, $resolution)
    {
        $data = [];

        $data['urlcount'] = 0;
        $data['start'] = $start;
        $data['end'] = $end;
        $data['duration'] = $data['end'] - $data['start'];

        if ($data['duration'] < 1) {
            throw new \Exception('End timestamp must be after start timestamp', 1512659945);
        }

        for ($slotStart = $start; $slotStart < $end; $slotStart += $resolution) {
            $slotEnd = min($slotStart + $resolution - 1, $end);
            $slotData = $this->getQueueRepository()->getPerformanceData($slotStart, $slotEnd);

            $slotUrlCount = 0;
            foreach ($slotData as $processId => &$processData) {
                $duration = $processData['end'] - $processData['start'];
                if ($processData['urlcount'] > 5 && $duration > 0) {
                    $processData['speed'] = 60 * 1 / ($duration / $processData['urlcount']);
                }
                $slotUrlCount += $processData['urlcount'];
            }

            $data['urlcount'] += $slotUrlCount;

            $data['slots'][$slotEnd] = [
                'amountProcesses' => count($slotData),
                'urlcount' => $slotUrlCount,
                'processes' => $slotData,
            ];

            if ($slotUrlCount > 5) {
                $data['slots'][$slotEnd]['speed'] = 60 * 1 / ($slotEnd - $slotStart / $slotUrlCount);
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
}
