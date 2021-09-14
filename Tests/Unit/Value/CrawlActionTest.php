<?php

declare(strict_types=1);

namespace TomasNorre\Crawler\Tests\Unit\Value;

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

use Nimut\TestingFramework\TestCase\UnitTestCase;
use TomasNorre\Crawler\Value\CrawlAction;

class CrawlActionTest extends UnitTestCase
{
    public const VALID_VALUE = 'start';

    /**
     * @test
     */
    public function toStringWithValidValueReturnsOriginalValue(): void
    {
        $crawlAction = new CrawlAction(self::VALID_VALUE);
        self::assertEquals(
            self::VALID_VALUE,
            $crawlAction->__toString()
        );
    }

    /**
     * @test
     */
    public function constructorThrowsException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        new CrawlAction('INVALID');
    }
}
