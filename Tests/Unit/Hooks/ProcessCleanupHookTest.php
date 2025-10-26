<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Hooks;

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
use AOE\Crawler\Process\Cleaner\OldProcessCleaner;
use AOE\Crawler\Process\Cleaner\OrphanProcessCleaner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ProcessCleanUpHook::class)]
class ProcessCleanupHookTest extends UnitTestCase
{
    #[Test]
    public function testCrawlerInitCallsCleanersOnce(): void
    {
        // Create mocks for the dependencies
        $orphanCleaner = $this->createMock(OrphanProcessCleaner::class);
        $oldCleaner = $this->createMock(OldProcessCleaner::class);

        // Expect the clean() method to be called exactly once on each
        $orphanCleaner->expects($this->once())
            ->method('clean');

        $oldCleaner->expects($this->once())
            ->method('clean');

        // Inject mocks into the hook
        $hook = new ProcessCleanUpHook($orphanCleaner, $oldCleaner);

        // Call the method under test
        $hook->crawler_init();
    }
}
