<?php
namespace AOE\Crawler\Command;

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
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\EventDispatcher;
use Helhum\Typo3Console\Mvc\Controller\CommandController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class CrawlerCommandController
 */
class CrawlerCommandController extends CommandController
{
    const CLI_STATUS_NOTHING_PROCCESSED = 0;
    const CLI_STATUS_REMAIN = 1; //queue not empty
    const CLI_STATUS_PROCESSED = 2; //(some) queue items where processed
    const CLI_STATUS_ABORTED = 4; //instance didn't finish
    const CLI_STATUS_POLLABLE_PROCESSED = 8;

    /**
     * @var string
     */
    protected $tableCrawlerProcess = 'tx_crawler_process';

    /**
     * Crawler Command - Cleaning up the queue.
     *
     * Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;
     * It will remove queue entries and perform a cleanup.
     *
     * Examples:
     *
     * --- Remove all finished queue-entries in the sub-branch of page 5
     * $ typo3cms crawler:flushqueue --mode finished --page 5
     *
     * --- Remove all pending queue-entries for all pages
     * $ typo3cms crawler:flushqueue --mode pending
     *
     * @param string $mode Output mode: "finished", "all", "pending"', "Specifies the type queue entries which is flushed in the process."
     * @param int $page Page to start clearing the queue recursively, 0 is default and clears all.
     *
     */
    public function flushQueueCommand($mode = 'finished', $page = 0)
    {
        /** @var CrawlerController $crawlerController */
        $crawlerController = $this->objectManager->get(CrawlerController::class);

        $pageId = MathUtility::forceIntegerInRange($page, 0);
        $fullFlush = ($pageId == 0);

        switch ($mode) {
            case 'all':
                $crawlerController->getLogEntriesForPageId($pageId, '', true, $fullFlush);
                break;
            case 'finished':
            case 'pending':
                $crawlerController->getLogEntriesForPageId($pageId, $mode, true, $fullFlush);
                break;
            default:
                $this->outputLine('<info>No matching parameters found.' . PHP_EOL . 'Try "typo3cms help crawler:flushqueue" to see your options</info>');
                break;

        }
    }

    /**
     * Crawler Command - Submitting URLs to be crawled.
     *
     * Works as a CLI interface to some functionality from the Web > Info > Site Crawler module;
     * It can put entries in the queue from command line options, return the list of URLs and even execute
     * all entries right away without having to queue them up - this can be useful for immediate re-cache,
     * re-indexing or static publishing from command line.
     *
     * Examples:
     *
     * --- Re-cache pages from page 7 and two levels down, executed immediately
     * $ typo3cms crawler:buildqueue --startpage 7 --depth 2 --conf <configurationKey> --mode exec
     *
     * --- Put entries for re-caching pages from page 7 into queue, 4 every minute.
     * $ typo3cms crawler:buildqueue --startpage 7 --depth 0 --conf <configurationKey> --number 4 --mode queue
     *
     * @param int $startpage The page from where the queue building should start.
     * @param int $depth Tree depth, 0-99', "How many levels under the 'page_id' to include.
     * @param string $mode Output mode: "url", "exec", "queue"', "Specifies output modes url : Will list URLs which wget could use as input. queue: Will put entries in queue table. exec: Will execute all entries right away!
     * @param int $number Specifies how many items are put in the queue per minute. Only valid for output mode "queue"
     * @param string $conf A comma separated list of crawler configurations
     */
    public function buildQueueCommand($startpage = 0, $depth = 0, $mode = '', $number = 0, $conf = '')
    {

        /** @var CrawlerController $crawlerController */
        $crawlerController = $this->objectManager->get(CrawlerController::class);

        if ($mode === 'exec') {
            $crawlerController->registerQueueEntriesInternallyOnly = true;
        }

        if (defined('TYPO3_MODE') && 'BE' === TYPO3_MODE) {
            // Crawler is called over TYPO3 BE
            $pageId = 1;
        } else {
            // Crawler is called over cli
            $pageId = MathUtility::forceIntegerInRange($startpage, 0);
        }

        $configurationKeys = $this->getConfigurationKeys($conf);

        if (!is_array($configurationKeys)) {
            $configurations = $crawlerController->getUrlsForPageId($pageId);
            if (is_array($configurations)) {
                $configurationKeys = array_keys($configurations);
            } else {
                $configurationKeys = [];
            }
        }

        if ($mode === 'queue' || $mode === 'exec') {
            $reason = new Reason();
            $reason->setReason(Reason::REASON_GUI_SUBMIT);
            $reason->setDetailText('The cli script of the crawler added to the queue');
            EventDispatcher::getInstance()->post(
                'invokeQueueChange',
                $crawlerController->setID,
                ['reason' => $reason]
            );
        }

        if ($crawlerController->extensionSettings['cleanUpOldQueueEntries']) {
            $crawlerController->cleanUpOldQueueEntries();
        }

        $crawlerController->setID = (int) GeneralUtility::md5int(microtime());
        $crawlerController->getPageTreeAndUrls(
            $pageId,
            MathUtility::forceIntegerInRange($depth, 0, 99),
            $crawlerController->getCurrentTime(),
            MathUtility::forceIntegerInRange($number ? intval($number) : 30, 1, 1000),
            $mode === 'queue' || $mode === 'exec',
            $mode === 'url',
            [],
            $configurationKeys
        );

        if ($mode === 'url') {
            $this->outputLine('<info>' . implode(PHP_EOL, $crawlerController->downloadUrls) . PHP_EOL . '</info>');
        } elseif ($mode === 'exec') {
            $this->outputLine('<info>Executing ' . count($crawlerController->urlList) . ' requests right away:</info>');
            $this->outputLine('<info>' . implode(PHP_EOL, $crawlerController->urlList) . '</info>' . PHP_EOL);
            $this->outputLine('<info>Processing</info>' . PHP_EOL);

            foreach ($crawlerController->queueEntries as $queueRec) {
                $p = unserialize($queueRec['parameters']);
                $this->outputLine('<info>' . $p['url'] . ' (' . implode(',', $p['procInstructions']) . ') => ' . '</info>' . PHP_EOL);
                $result = $crawlerController->readUrlFromArray($queueRec);

                $requestResult = unserialize($result['content']);
                if (is_array($requestResult)) {
                    $resLog = is_array($requestResult['log']) ? PHP_EOL . chr(9) . chr(9) . implode(PHP_EOL . chr(9) . chr(9), $requestResult['log']) : '';
                    $this->outputLine('<info>OK: ' . $resLog . '</info>' . PHP_EOL);
                } else {
                    $this->outputLine('<errror>Error checking Crawler Result:  ' . substr(preg_replace('/\s+/', ' ', strip_tags($result['content'])), 0, 30000) . '...' . PHP_EOL . '</errror>' . PHP_EOL);
                }
            }
        } elseif ($mode === 'queue') {
            $this->outputLine('<info>Putting ' . count($crawlerController->urlList) . ' entries in queue:</info>' . PHP_EOL);
            $this->outputLine('<info>' . implode(PHP_EOL, $crawlerController->urlList) . '</info>' . PHP_EOL);
        } else {
            $this->outputLine('<info>' . count($crawlerController->urlList) . ' entries found for processing. (Use --mode to decide action):</info>' . PHP_EOL);
            $this->outputLine('<info>' . implode(PHP_EOL, $crawlerController->urlList) . '</info>' . PHP_EOL);
        }
    }

    /**
     * Crawler Command - Crawling the URLs from the queue
     *
     * Examples:
     *
     * --- Will trigger the crawler which starts to process the queue entires
     * $ typo3cms crawler:crawlqueue
     *
     * @param int $amount How many pages should be crawled during that run.
     * @param int $sleeptime Amount of milliseconds which the system should use to relax between crawls.
     * @param int $sleepafter Amount of seconds which the system should use to relax after all crawls are done.
     *
     * @return int
     */
    public function crawlQueueCommand($amount = 0, $sleeptime = 0, $sleepafter = 0)
    {
        $result = self::CLI_STATUS_NOTHING_PROCCESSED;

        /** @var CrawlerController $crawlerController */
        $crawlerController = $this->objectManager->get(CrawlerController::class);
        /** @var QueueRepository $queueRepository */
        $queueRepository = $this->objectManager->get(QueueRepository::class);

        $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);
        $settings = is_array($settings) ? $settings : [];
        $crawlerController->setExtensionSettings($settings);

        if (!$crawlerController->getDisabled() && $crawlerController->CLI_checkAndAcquireNewProcess($crawlerController->CLI_buildProcessId())) {
            $countInARun = $amount ? intval($amount) : $crawlerController->extensionSettings['countInARun'];
            $sleepAfterFinish = $sleeptime ? intval($sleeptime) : $crawlerController->extensionSettings['sleepAfterFinish'];
            $sleepTime = $sleepafter ? intval($sleepafter) : $crawlerController->extensionSettings['sleepTime'];

            try {
                // Run process:
                $result = $crawlerController->CLI_run($countInARun, $sleepTime, $sleepAfterFinish);
            } catch (\Exception $e) {
                $this->outputLine('<warning>'. get_class($e) . ': ' . $e->getMessage() . '</warning>');
                $result = self::CLI_STATUS_ABORTED;
            }

            // Cleanup
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableCrawlerProcess);
            $queryBuilder
                ->delete($this->tableCrawlerProcess)
                ->where(
                    $queryBuilder->expr()->eq('assigned_item_count', 0)
                )
                ->execute();

            $crawlerController->CLI_releaseProcesses($crawlerController->CLI_buildProcessId());

            $this->outputLine('<info>Unprocessed Items remaining:' . $queueRepository->countUnprocessedItems() . ' (' . $crawlerController->CLI_buildProcessId() . ')</info>');
            $result |= ($queueRepository->countUnprocessedItems() > 0 ? self::CLI_STATUS_REMAIN : self::CLI_STATUS_NOTHING_PROCCESSED);
        } else {
            $result |= self::CLI_STATUS_ABORTED;
        }

        return $result;

    }

    /**
     * Obtains configuration keys from the CLI arguments
     *
     * @param $conf string
     * @return array
     */
    private function getConfigurationKeys($conf)
    {
        $parameter = trim($conf);
        return ($parameter != '' ? GeneralUtility::trimExplode(',', $parameter) : []);
    }
}
