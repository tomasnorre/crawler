<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Helper\Sleeper;

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

use AOE\Crawler\Helper\Sleeper\SystemSleeper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(SystemSleeper::class)]
class SystemSleeperTest extends UnitTestCase
{
    #[Test]
    public function sleepSleeps(): void
    {
        $sleepTime = 2;
        $sleeper = new SystemSleeper();
        $start = time();
        $sleeper->sleep($sleepTime);
        $end = time();
        $this->assertGreaterThanOrEqual($start, $end);
        $this->assertEquals($sleepTime, $end - $start);
    }
}
