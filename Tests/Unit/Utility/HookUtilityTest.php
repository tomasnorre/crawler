<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Utility;

/*
 * (c) 2025 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use AOE\Crawler\Utility\HookUtility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(HookUtility::class)]
class HookUtilityTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function testRegisterHooksRegistersExpectedHooks(): void
    {
        $extKey = 'crawler';

        // Reset global state before calling
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['cli_hooks'] = [];

        // ✅ This direct call requires the method to be PUBLIC
        HookUtility::registerHooks($extKey);

        $this->assertContains(
            ProcessCleanUpHook::class,
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['cli_hooks'],
            'registerHooks should register ProcessCleanUpHook'
        );

        $this->assertContains(
            ProcessCleanUpHook::class,
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey]['refresh_hooks'],
            'registerHooks should register ProcessCleanUpHook'
        );

        $this->assertContains(
            "AOE\Crawler\Hooks\DataHandlerHook->addFlushedPagesToCrawlerQueue",
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'],
            'registerHooks should register clearPageCacheEval'
        );
    }

    public function testRegisterHooksIsPublic(): void
    {
        $ref = new \ReflectionMethod(HookUtility::class, 'registerHooks');
        self::assertTrue($ref->isPublic(), 'registerHooks should be public');
    }
}
