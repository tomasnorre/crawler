<?php

declare(strict_types=1);

namespace AOE\Crawler\Utility;

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

use AOE\Crawler\Backend\BackendModule;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * @codeCoverageIgnore
 * @internal since v9.2.5
 * @deprecated since 12.0.0 will be removed when dropping support for TYPO3 11
 */
class BackendUtility
{
    /**
     * Registers the crawler info module function
     */
    public static function registerInfoModuleFunction(): void
    {
        ExtensionManagementUtility::insertModuleFunction(
            'web_info',
            BackendModule::class,
            null,
            'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:moduleFunction.tx_crawler_modfunc1'
        );
    }
}
