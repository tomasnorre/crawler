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

use AOE\Crawler\Hooks\ProcessCleanUpHook;

/**
 * @codeCoverageIgnore
 * @internal since v9.2.5
 */
class HookUtility
{
    /**
     * Registers hooks
     *
     * @param string $extKey
     */
    public static function registerHooks($extKey): void
    {
        // Activating Crawler cli_hooks
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['cli_hooks'][] =
            ProcessCleanUpHook::class;

        // Activating refresh hooks
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['refresh_hooks'][] =
            ProcessCleanUpHook::class;

        // Env-dependent
        self::registerBackendHooks();

    }

    private static function registerBackendHooks(): void
    {
        // DataHandler clear page cache pre-processing
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'][] =
            "AOE\Crawler\Hooks\DataHandlerHook->addFlushedPagesToCrawlerQueue";
    }
}
