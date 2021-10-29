<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Tests\Unit\Value;

use AOE\Crawler\Value\QueueFilter;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * @covers \AOE\Crawler\Value\QueueFilter
 */
class QueueFilterTest extends UnitTestCase
{
    public const VALID_VALUE = 'finished';

    /**
     * @test
     */
    public function defaultValueConstructor(): void
    {
        self::assertEquals(
            'all',
            new QueueFilter()
        );
    }

    /**
     * @test
     */
    public function toStringWithValidValueReturnsOriginalValue(): void
    {
        $queueFilter = new QueueFilter(self::VALID_VALUE);
        self::assertEquals(
            self::VALID_VALUE,
            $queueFilter->__toString()
        );
    }

    /**
     * @test
     */
    public function constructorThrowsException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        new QueueFilter('INVALID');
    }
}
