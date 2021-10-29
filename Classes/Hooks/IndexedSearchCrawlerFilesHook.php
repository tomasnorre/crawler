<?php

declare(strict_types=1);

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

namespace AOE\Crawler\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Crawler hook for indexed search. Works with the "crawler" extension
 * This hook is specifically used to index external files found on pages through the crawler extension.
 * @see \TYPO3\CMS\IndexedSearch\Indexer::extractLinks()
 * @internal this is a TYPO3-internal hook implementation and not part of TYPO3's Core API.
 */
class IndexedSearchCrawlerFilesHook
{
    /**
     * Call back function for execution of a log element
     *
     * @param array $params Params from log element.
     * @return array|null Result array
     */
    public function crawler_execute($params)
    {
        if (! is_array($params['conf'])) {
            return null;
        }
        // Initialize the indexer class:
        $indexerObj = GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
        $indexerObj->conf = $params['conf'];
        $indexerObj->init();
        // Index document:
        if ($params['alturl']) {
            $fI = pathinfo($params['document']);
            $ext = strtolower($fI['extension']);
            $indexerObj->indexRegularDocument($params['alturl'], true, $params['document'], $ext);
        } else {
            $indexerObj->indexRegularDocument($params['document'], true);
        }
        // Return OK:
        return ['content' => []];
    }
}
