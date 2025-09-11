<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Process;

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

use AOE\Crawler\Process\ProcessManagerFactory;
use AOE\Crawler\Process\UnixProcessManager;
use AOE\Crawler\Process\WindowsProcessManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ProcessManagerFactory::class)]
class ProcessManagerFactoryTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function factoryReturnsCorrectManagerOnUnix(): void
    {
        if (!Environment::isUnix()) {
            $this->markTestSkipped('This test is for Unix systems only');
        }
        self::assertInstanceOf(UnixProcessManager::class, (new ProcessManagerFactory())->create());
    }

    #[Test]
    public function factoryReturnsCorrectManagerOnWindows(): void
    {
        if (!Environment::isWindows()) {
            $this->markTestSkipped('This test is for Windows systems only');
        }
        self::assertInstanceOf(WindowsProcessManager::class, (new ProcessManagerFactory())->create());
    }
}
