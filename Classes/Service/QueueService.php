<?php

declare(strict_types=1);

namespace TomasNorre\Crawler\Service;

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

use TomasNorre\Crawler\Controller\CrawlerController;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class QueueService
{
    /**
     * @var CrawlerController
     */
    private $crawlerController;

    public function injectCrawlerController(CrawlerController $crawlerController): void
    {
        $this->crawlerController = $crawlerController;
        $this->crawlerController->setID = GeneralUtility::md5int(microtime());
    }

    public function addPageToQueue(int $pageUid, int $time = 0): void
    {
        /**
         * Todo: Switch back to getPage(); when dropping support for TYPO3 9 LTS - TNM
         * This switch to getPage_noCheck() is needed as TYPO3 9 LTS doesn't return dokType < 200, therefore automatically
         * adding pages to crawler queue when editing page-titles from the page tree directly was not working.
         */
        $pageData = GeneralUtility::makeInstance(PageRepository::class)->getPage_noCheck($pageUid, true);
        $configurations = $this->crawlerController->getUrlsForPageRow($pageData);
        // Currently this is only used from the DataHandlerHook, and we don't know of any allowed/disallowed configurations,
        // when clearing the cache, therefore we allow all configurations in this case.
        // This next lines could be skipped as it will return the incomming configurations, but for visibility and
        // later implementation it's kept as it do no harm.
        $allowedConfigurations = [];
        $configurations = ConfigurationService::removeDisallowedConfigurations($allowedConfigurations, $configurations);
        $downloadUrls = [];
        $duplicateTrack = [];

        if (is_array($configurations)) {
            foreach ($configurations as $configuration) {
                //enable inserting of entries
                $this->crawlerController->registerQueueEntriesInternallyOnly = false;
                $this->crawlerController->urlListFromUrlArray(
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
                unset($this->crawlerController->queueEntries);
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
}
