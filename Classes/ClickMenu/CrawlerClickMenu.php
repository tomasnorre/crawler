<?php
namespace AOE\Crawler\ClickMenu;

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
     * @param ClickMenu reference parent object
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

        $additionalParameters = [];
        $additionalParameters[] = 'SET[function]=tx_crawler_modfunc1';
        $additionalParameters[] = 'SET[crawlaction]=start';
        $additionalParameters[] = 'configurationSelection[]=' . $crawlerConfiguration['name'];

        $additionalMenuItems = [];
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
            ExtensionManagementUtility::extRelPath('crawler') . 'icon_tx_crawler_configuration.gif'
        );

        return $icon;
    }
}
