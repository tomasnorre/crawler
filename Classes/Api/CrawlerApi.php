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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class CrawlerApi
 *
 * @package AOE\Crawler\Api
 */
class CrawlerApi
{
    /**
     * @var CrawlerController|Object
     */
    private $crawlerController;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var array
     */
    protected $allowedConfigurations = [];

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_queue';

    public function __construct()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->crawlerController = $objectManager->get(CrawlerController::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
    }

    /**
     * Each crawler run has a setid, this facade method delegates
     * the it to the crawler object
     *
     * @param int $id
     * @throws \Exception
     */
    public function overwriteSetId(int $id)
    {
        $this->findCrawler()->setID = $id;
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
     *
     * @throws \Exception
     * @return void
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
        }
    }

    /**
     * Counts all entries in the database which are scheduled for a given page id and a schedule timestamp.
     *
     * @param int $page_uid
     * @param int $schedule_timestamp
     *
     * @return int
     */
    protected function countEntriesInQueueForPageByScheduleTime($page_uid, $schedule_timestamp)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $count = $queryBuilder
            ->count('*')
            ->from($this->tableName);

        //if the same page is scheduled for the same time and has not be executed?
        //un-timed elements need an exec_time with 0 because they can occur multiple times
        if ($schedule_timestamp == 0) {
            $count->where(
                $queryBuilder->expr()->eq('page_id', $page_uid),
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->eq('scheduled', $schedule_timestamp)
            );
        } else {
            //timed elements have got a fixed schedule time, if a record with this time
            //exists it is maybe queued for the future, or is has been queue for the past and therefore
            //also been processed.
            $count->where(
                $queryBuilder->expr()->eq('page_id', $page_uid),
                $queryBuilder->expr()->eq('scheduled', $schedule_timestamp)
            );
        }

        return $count->execute()->rowCount();
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $query = $queryBuilder
            ->from($this->tableName)
            ->selectLiteral('max(scheduled) as latest')
            ->where(
                $queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($uid))
            );

        if ($future_crawldates_only) {
            $query->andWhere(
                $queryBuilder->expr()->gt('scheduled', time())
            );
        }

        if ($unprocessed_only) {
            $query->andWhere(
                $queryBuilder->expr()->eq('exec_time', 0)
            );
        }

        $row = $query->execute()->fetch(0);
        if ($row['latest']) {
            $res = $row['latest'];
        } else {
            $res = 0;
        }

        return intval($res);
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $statement = $queryBuilder
            ->from($this->tableName)
            ->select('scheduled', 'exec_time', 'set_id')
            ->where(
                $queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            );
        if ($limit) {
            $statement->setMaxResults($limit);
        }

        return $statement->execute()->fetchAll();
    }

    /**
     * Reads the registered processingInstructions of the crawler
     *
     * @return array
     */
    private function getCrawlerProcInstructions(): array
    {
        $crawlerProcInstructions = [];
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] as $configuration) {
                $crawlerProcInstructions[$configuration['key']] = $configuration['value'];
            }
        }

        return $crawlerProcInstructions;
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
            'assignedButUnprocessed' => $this->queueRepository->countAllAssignedPendingItems(),
            'unprocessed' => $this->queueRepository->countAllPendingItems()
        ];
    }

    /**
     * Get queue statistics by configuration
     *
     * @return array array of array('configuration' => <>, 'assignedButUnprocessed' => <>, 'unprocessed' => <>)
     */
    public function getQueueStatisticsByConfiguration()
    {
        $statistics = $this->queueRepository->countPendingItemsGroupedByConfigurationKey();
        $setIds = $this->queueRepository->getSetIdWithUnprocessedEntries();
        $totals = $this->queueRepository->getTotalQueueEntriesByConfiguration($setIds);

        // "merge" arrays
        foreach ($statistics as &$value) {
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
        return $this->queueRepository->getLastProcessedEntries('*', $limit);
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
        $lastProcessedEntries = $this->queueRepository->getLastProcessedEntriesTimestamps();

        if (count($lastProcessedEntries) < 10) {
            // not enough information
            return false;
        }

        $tooOldDelta = 60; // time between two entries is "too old"

        $compareValue = time();
        $startTime = $lastProcessedEntries[0];

        $pages = 0;

        reset($lastProcessedEntries);
        foreach ($lastProcessedEntries as $timestamp) {
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

        return $pages / ($time / 60);
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
            $slotData = $this->queueRepository->getPerformanceData($slotStart, $slotEnd);

            $slotUrlCount = 0;
            foreach ($slotData as &$processData) {
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
