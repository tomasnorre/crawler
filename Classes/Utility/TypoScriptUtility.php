<?php
namespace AOE\Crawler\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TypoScriptUtility
 *
 * @package AOE\Crawler\Utility
 */
class TypoScriptUtility
{

    /**
     * @param int $pageId
     *
     * @return int
     * @throws \Exception
     */
    public static function getPageUidForTypoScriptRootTemplateInRootLine($pageId)
    {

        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);

        if ($cacheManager->getCache(CacheUtility::CACHE_ROOTLINE)->has($pageId)) {
            return $cacheManager->getCache(CacheUtility::CACHE_ROOTLINE)->get($pageId);
        }

        $pageRootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pageId);

        foreach ($pageRootLine as $page) {
            $templateUid = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                '*',
                'sys_template',
                'root=1 AND pid=' . (int)$page['uid'] .
                \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('sys_template')
            );

            if (null !== $templateUid['pid']) {
                $cacheManager->getCache(CacheUtility::CACHE_ROOTLINE)->set($pageId, $templateUid['pid']);
                return $templateUid['pid'];
            }

        }

        throw new \Exception('No TypoScript template found', 1478611016);

    }
}