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
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @codeCoverageIgnore
 * @internal since v9.2.5
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

    /**
     * Registers icons for use in the IconFactory
     */
    public static function registerIcons(): void
    {
        if ((new Typo3Version())->getMajorVersion() === 10) {
            // This method can be deleted once compatibility with TYPO3 v10 is removed.
            // See Configuration/Icons.php for the way to go in TYPO3 v11+
            self::registerCrawlerIcon();
            self::registerStartIcon();
            self::registerStopIcon();
        }
    }

    /**
     * Register Start Icon
     */
    private static function registerStartIcon(): void
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
     */
    private static function registerStopIcon(): void
    {
        /** @var IconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $iconRegistry->registerIcon(
            'tx-crawler-stop',
            SvgIconProvider::class,
            ['source' => 'EXT:crawler/Resources/Public/Icons/crawler_stop.svg']
        );
    }

    private static function registerCrawlerIcon(): void
    {
        /** @var IconRegistry $iconRegistry */
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $iconRegistry->registerIcon(
            'tx-crawler',
            SvgIconProvider::class,
            ['source' => 'EXT:crawler/Resources/Public/Icons/crawler_configuration.svg']
        );
    }
}
