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

/**
 * Class HookUtility
 * @package AOE\Crawler\Utility
 */
class HookUtility
{

    public static function registerHooks($extKey)
    {
        if (TYPO3_MODE == 'BE') {
            self::registerBackendHooks($extKey);
        } else {
            self::registerFrontendHooks($extKey);
        }
    }

    /**
     * Registers hooks that are only valid for the backend
     * Use in ext_localconf.php
     * @param $extKey
     *
     * @return void
     */
    protected static function registerBackendHooks($extKey)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']['tx_crawler'] =
            'AOE\\Crawler\\Hooks\\TsfeHook->fe_init';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser']['tx_crawler'] =
            'AOE\\Crawler\\Hooks\\TsfeHook->fe_feuserInit';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['tx_crawler'] =
            'AOE\\Crawler\\Hooks\\TsfeHook->fe_isOutputting';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['tx_crawler'] =
            'AOE\\Crawler\\Hooks\\TsfeHook->fe_eofe';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nc_staticfilecache/class.tx_ncstaticfilecache.php']['createFile_initializeVariables']['tx_crawler'] =
            'AOE\\Crawler\\Hooks\\StaticFileCacheCreateUriHook->initialize';

        // Activating cli_hooks
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['cli_hooks'][] =
            'AOE\\Crawler\\Hooks\\ProcessCleanUpHook';

        // Activating refresh hooks
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['refresh_hooks'][] =
            'AOE\\Crawler\\Hooks\\ProcessCleanUpHook';
    }

    /**
     * Registers hooks that are only valid for the frontend
     * Use in ext_localconf.php
     *
     * @param $extKey
     *
     * @return void
     */
    protected static function registerFrontendHooks($extKey)
    {

    }
}