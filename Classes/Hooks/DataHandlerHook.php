<?php

declare(strict_types=1);

namespace AOE\Crawler\Hooks;

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

use AOE\Crawler\Api\CrawlerApi;
use AOE\Crawler\Domain\Repository\QueueRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DataHandlerHook
{
    public function addFlushedPagesToCrawlerQueue(array $parameters, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler): void
    {
        $pageIdsToBeFlushedFromCache = $parameters['pageIdArray'];
        if (empty($pageIdsToBeFlushedFromCache)) {
            return;
        }
        foreach ($pageIdsToBeFlushedFromCache as $pageId) {
            $pageId = (int) $pageId;
            if ($pageId < 1) {
                continue;
            }
            if ($this->getQueueRepository()->isPageInQueue($pageId)) {
                continue;
            }
            $this->getCrawlerApi()->addPageToQueue($pageId);
        }
    }

    private function getQueueRepository(): QueueRepository
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(QueueRepository::class);
    }

    private function getCrawlerApi(): CrawlerApi
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(CrawlerApi::class);
    }
}
