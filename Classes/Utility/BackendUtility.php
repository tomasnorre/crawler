<?php

namespace AOE\Crawler\Utility;

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

use AOE\Crawler\Backend\BackendModule;
use AOE\Crawler\ClickMenu\CrawlerClickMenu;
use AOE\Crawler\ContextMenu\ItemProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BackendUtility
 *
 * @codeCoverageIgnore
 */
class BackendUtility
{
    /**
     * Registers the crawler info module function
     *
     * @return void
     */
    public static function registerInfoModuleFunction()
    {
        ExtensionManagementUtility::insertModuleFunction(
            'web_info',
            BackendModule::class,
            null,
            'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:moduleFunction.tx_crawler_modfunc1'
        );
    }

    /**
     * Registers the crawler click menu item
     *
     * @return void
     */
    public static function registerClickMenuItem()
    {
        // Use old Click Menu for versions smaller than TYPO3 8.6
        // https://docs.typo3.org/typo3cms/extensions/core/Changelog/8.6/Breaking-78192-RefactorClickMenuContextMenu.html
        $versionSmallerThanEightSix = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 8006000;
        if ($versionSmallerThanEightSix) {
            $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = [
                'name' => CrawlerClickMenu::class,
            ];
        }
    }

    /**
     * Registers the context sensitive help for TCA fields
     *
     * @return void
     */
    public static function registerContextSensitiveHelpForTcaFields()
    {
        ExtensionManagementUtility::addLLrefForTCAdescr(
            'tx_crawler_configuration',
            'EXT:crawler/Resources/Private/Language/locallang_csh_tx_crawler_configuration.xlf'
        );
    }

    /**
     * Registers icons for use in the IconFactory
     *
     * @return void
     */
    public static function registerIcons()
    {
        self::registerStartIcon();
        self::registerStopIcon();
    }

    /**
     * Register Start Icon
     *
     * @return void
     */
    private static function registerStartIcon()
    {
        /** @var IconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $iconRegistry->registerIcon(
            'tx-crawler-start',
            SvgIconProvider::class,
            ['source' => 'EXT:crawler/Resources/Public/Icons/crawler_start.svg']
        );
    }

    /**
     * Register Stop Icon
     *
     * @return void
     */
    private static function registerStopIcon()
    {
        /** @var IconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $iconRegistry->registerIcon(
            'tx-crawler-stop',
            SvgIconProvider::class,
            ['source' => 'EXT:crawler/Resources/Public/Icons/crawler_stop.svg']
        );
    }
}
