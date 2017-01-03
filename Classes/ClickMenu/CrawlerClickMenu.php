<?php
namespace AOE\Crawler\ClickMenu;

/*
 * Copyright notice
 *
 * (c) 2016 AOE GmbH <dev@aoe.com>
 *
 * All rights reserved
 * This file is part of the TYPO3 CMS project.
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

use TYPO3\CMS\Backend\ClickMenu\ClickMenu;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class CrawlerClickMenu
 *
 * @package AOE\Crawler\ClickMenu
 */
class CrawlerClickMenu
{
    /**
     * Main function
     *
     * @param ClickMenu $backRef reference parent object
     * @param array $menuItems
     * @param string $tableName
     * @param integer $uid
     *
     * @return array
     */
    public function main(ClickMenu $backRef, array $menuItems, $tableName, $uid)
    {
        if ('tx_crawler_configuration' !== $tableName) {
            return $menuItems;
        }

        $crawlerConfiguration = BackendUtility::getRecord(
            $tableName,
            $uid,
            'pid, name'
        );

        if (!$crawlerConfiguration) {
            return $menuItems;
        }

        $additionalParameters = array();
        $additionalParameters[] = 'SET[function]=tx_crawler_modfunc1';
        $additionalParameters[] = 'SET[crawlaction]=start';
        $additionalParameters[] = 'configurationSelection[]=' . $crawlerConfiguration['name'];

        $additionalMenuItems = array();
        $additionalMenuItems[] = $backRef->linkItem(
            LocalizationUtility::translate(
                'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:contextMenu.label',
                'crawler'
            ),
            $this->getContextMenuIcon(),
            'top.goToModule(\'web_info\', 1, \'&' . implode('&', $additionalParameters) . '\'); return hideCM();'
        );

        return array_merge($menuItems, $additionalMenuItems);
    }

    /**
     * Helper function to render the context menu icon
     *
     * @return string
     */
    private function getContextMenuIcon()
    {
        $icon = sprintf(
            '<img src="%s" border="0" align="top" alt="" />',
            ExtensionManagementUtility::siteRelPath('crawler') . 'icon_tx_crawler_configuration.gif'
        );

        return $icon;
    }
}
