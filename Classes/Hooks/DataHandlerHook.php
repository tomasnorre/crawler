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
    /**
     * @param array $parameters
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function addFlushedPagesToCrawlerQueue(array $parameters, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler): void
    {
        if ($dataHandler->BE_USER->workspace > 0 && !$this->isWorkspacePublishAction($dataHandler->cmdmap)) {
            return;
        }

        $pageIdsToBeFlushedFromCache = $parameters['pageIdArray'];
        if (empty($pageIdsToBeFlushedFromCache)) {
            return;
        }
        foreach ($pageIdsToBeFlushedFromCache as $pageId) {
            $pageId = (int)$pageId;
            if ($pageId < 1) {
                continue;
            }
            if ($this->getQueueRepository()->isPageInQueue($pageId)) {
                continue;
            }
            $this->getCrawlerApi()->addPageToQueue($pageId);
        }
    }

    /**
     * Checks if a workspace record is being published
     *
     * Example $commandMap structure as provided by TYPO3:
     * 'pages' => array(
     *     123 => array(
     *         'version' => array(
     *             'action' => 'swap'
     *  [...]
     * 'tt_content' => array(
     *     456 => array(
     *         'version' => array(
     *             'action' => 'swap'
     * [...]
     *
     * @param array $commandMap
     * @return bool
     */
    private function isWorkspacePublishAction(array $commandMap): bool
    {
        $isWorkspacePublishAction = false;
        foreach ($commandMap as $tableCommandMap) {
            if (!is_array($tableCommandMap)) {
                continue;
            }
            foreach ($tableCommandMap as $singleCommandMap) {
                if (!is_array($singleCommandMap)) {
                    continue;
                }
                if (!$this->isSwapAction($singleCommandMap)) {
                    continue;
                }
                $isWorkspacePublishAction = true;
                return $isWorkspacePublishAction;
            }
        }
        return $isWorkspacePublishAction;
    }

    /**
     * Checks if a page is being swapped with it's workspace overlay
     *
     * @param array $singleCommandMap
     * @return bool
     */
    private function isSwapAction(array $singleCommandMap): bool
    {
        $isSwapAction = false;
        if (
            isset($singleCommandMap['version'])
            && is_array($singleCommandMap['version'])
            && isset($singleCommandMap['version']['action'])
            && 'swap' === $singleCommandMap['version']['action']
        ) {
            $isSwapAction = true;
        }
        return $isSwapAction;
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
