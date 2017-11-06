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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class BackendUtility
 *
 * @package AOE\Crawler\Utility
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
            'tx_crawler_modfunc1',
            null,
            'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:moduleFunction.tx_crawler_modfunc1'
        );
    }

    /**
     * Registers the crawler clickmenu item
     *
     * @return void
     */
    public static function registerClickMenuItem()
    {
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
            'name' => 'AOE\\Crawler\\ClickMenu\\CrawlerClickMenu'
        );
    }

    /**
     * Registers the context sensitive help for TCA fields
     *
     * @return void
     */
    public static function registerContextSensitiveHelpForTcaFields(){
        ExtensionManagementUtility::addLLrefForTCAdescr(
            'tx_crawler_configuration',
            'EXT:crawler/Resources/Private/Language/locallang_csh_tx_crawler_configuration.xlf'
        );
    }
}
