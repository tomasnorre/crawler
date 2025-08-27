<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class QueueService
{
    public function __construct(
        private readonly CrawlerController $crawlerController
    ) {
        if ($this->crawlerController->setID <= 0) {
            $this->crawlerController->setID = GeneralUtility::md5int(microtime());
        }
    }

    public function addPageToQueue(int $pageUid, int $time = 0): void
    {
        $pageData = GeneralUtility::makeInstance(PageRepository::class)->getPage($pageUid, true);
        $configurations = $this->crawlerController->getUrlsForPageRow($pageData);
        // Currently this is only used from the DataHandlerHook, and we don't know of any allowed/disallowed configurations,
        // when clearing the cache, therefore we allow all configurations in this case.
        // This next lines could be skipped as it will return the incoming configurations, but for visibility and
        // later implementation it's kept as it do no harm.
        $allowedConfigurations = [];
        $configurations = ConfigurationService::removeDisallowedConfigurations($allowedConfigurations, $configurations);
        $downloadUrls = [];
        $duplicateTrack = [];

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

    /**
     * Reads the registered processingInstructions of the crawler
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
}
