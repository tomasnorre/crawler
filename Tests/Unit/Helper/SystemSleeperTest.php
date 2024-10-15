<?php

declare(strict_types=1);

/*
 * (c) 2024-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Tests\Unit\Helper;

use AOE\Crawler\Helper\Sleeper\SystemSleeper;
use PHPUnit\Framework\Attributes\CoversClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(SystemSleeper::class)]
class SystemSleeperTest extends UnitTestCase
{


    public function testSystemSleeper(): void
    {
        $this->resetSingletonInstances = true;

        $startTime = date('s');
        $subject = new SystemSleeper();
        $subject->sleep(1);
        $endTime = date('s');
        self::assertEquals(1, $endTime - $startTime);
    }
}
