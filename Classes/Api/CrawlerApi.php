<?php

declare(strict_types=1);

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
use AOE\Crawler\Exception\CrawlerObjectException;
use AOE\Crawler\Exception\TimeStampException;
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

    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    public function __construct()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
    }

    /**
     * Each crawler run has a setid, this facade method delegates
     * the it to the crawler object
     *
     * @throws \Exception
     */
    public function overwriteSetId(int $id): void
    {
        $this->findCrawler()->setID = $id;
    }

    /**
     * This method is used to limit the configuration selection to
     * a set of configurations.
     */
    public function setAllowedConfigurations(array $allowedConfigurations): void
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
     * Adds a page to the crawlerqueue by uid
     *
     * @param int $uid uid
     */
    public function addPageToQueue($uid): void
    {
        $uid = intval($uid);
        //non timed elements will be added with timestamp 0
        $this->addPageToQueueTimed($uid, 0);
    }

    /**
     * Adds a page to the crawlerqueue by uid and sets a
     * timestamp when the page should be crawled.
     *
     * @param int $uid pageid
     * @param int $time timestamp
     *
     * @throws \Exception
     */
    public function addPageToQueueTimed($uid, $time): void
    {
        $uid = intval($uid);
        $time = intval($time);

        $crawler = $this->findCrawler();
        /**
         * Todo: Switch back to getPage(); when dropping support for TYPO3 9 LTS - TNM
         * This switch to getPage_noCheck() is needed as TYPO3 9 LTS doesn't return dokType < 200, therefore automatically
         * adding pages to crawler queue when editing page-titles from the page tree directly was not working.
         */
        $pageData = GeneralUtility::makeInstance(PageRepository::class)->getPage_noCheck($uid, true);
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
     * @return array array with the crawl-history of a page => 0 : scheduled time , 1 : executed_time, 2 : set_id
     */
    public function getCrawlHistoryForPage(int $uid, int $limit = 0)
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
     * Get queue statistics
     */
    public function getQueueStatistics(): array
    {
        return [
            'assignedButUnprocessed' => $this->queueRepository->countAllAssignedPendingItems(),
            'unprocessed' => $this->queueRepository->countAllPendingItems(),
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
     */
    public function getActiveProcessesCount(): int
    {
        $processRepository = new ProcessRepository();
        return $processRepository->findAllActive()->count();
    }

    public function getLastProcessedQueueEntries(int $limit): array
    {
        return $this->queueRepository->getLastProcessedEntries($limit);
    }

    /**
     * Get current crawling speed
     *
     * @return int|float|bool
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
     * @throws TimeStampException
     */
    public function getPerformanceData($start, $end, $resolution)
    {
        $data = [];

        $data['urlcount'] = 0;
        $data['start'] = $start;
        $data['end'] = $end;
        $data['duration'] = $data['end'] - $data['start'];

        if ($data['duration'] < 1) {
            throw new TimeStampException('End timestamp must be after start timestamp', 1512659945);
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

    /**
     * Method to get an instance of the internal crawler singleton
     *
     * @return CrawlerController Instance of the crawler lib
     *
     * @throws CrawlerObjectException
     */
    protected function findCrawler()
    {
        if (! is_object($this->crawlerController)) {
            $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
            $this->crawlerController->setID = GeneralUtility::md5int(microtime());
        }

        if (is_object($this->crawlerController)) {
            return $this->crawlerController;
        }
        throw new CrawlerObjectException('no crawler object', 1512659759);
    }

    /**
     * This method is used to limit the processing instructions to the processing instructions
     * that are allowed.
     */
    protected function filterUnallowedConfigurations(array $configurations): array
    {
        if (count($this->allowedConfigurations) > 0) {
            // 	remove configuration that does not match the current selection
            foreach ($configurations as $confKey => $confArray) {
                if (! in_array($confKey, $this->allowedConfigurations, true)) {
                    unset($configurations[$confKey]);
                }
            }
        }

        return $configurations;
    }

    /**
     * Reads the registered processingInstructions of the crawler
     */
    private function getCrawlerProcInstructions(): array
    {
        $crawlerProcInstructions = [];
        if (! empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] as $configuration) {
                $crawlerProcInstructions[$configuration['key']] = $configuration['value'];
            }
        }

        return $crawlerProcInstructions;
    }
}
