<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Exception\CrawlerObjectException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

class QueueService
{
    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    public function addPageToQueue(int $pageUid, int $time = 0): void
    {
        $crawler = $this->findCrawler();
        /**
         * Todo: Switch back to getPage(); when dropping support for TYPO3 9 LTS - TNM
         * This switch to getPage_noCheck() is needed as TYPO3 9 LTS doesn't return dokType < 200, therefore automatically
         * adding pages to crawler queue when editing page-titles from the page tree directly was not working.
         */
        $pageData = GeneralUtility::makeInstance(PageRepository::class)->getPage_noCheck($pageUid, true);
        $configurations = $crawler->getUrlsForPageRow($pageData);
        $allowedConfigurations = [];
        $configurations = ConfigurationService::removeDisallowedConfigurations($allowedConfigurations, $configurations);
        $downloadUrls = [];
        $duplicateTrack = [];

        if (is_array($configurations)) {
            foreach ($configurations as $configuration) {
                //enable inserting of entries
                $crawler->registerQueueEntriesInternallyOnly = false;
                $crawler->urlListFromUrlArray(
                    $configuration,
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

    /**
     * Method to get an instance of the internal crawler singleton
     *
     * @return CrawlerController Instance of the crawler lib
     *
     * @throws CrawlerObjectException
     */
    private function findCrawler()
    {
        if (! is_object($this->crawlerController)) {
            $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
            $this->crawlerController->setID = GeneralUtility::md5int(microtime());
        }

        if (is_object($this->crawlerController)) {
            return $this->crawlerController;
        }
        throw new CrawlerObjectException('no crawler object', 1608465082);
    }
}
