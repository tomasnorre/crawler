<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Value;

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

use AOE\Crawler\Value\QueueFilter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Value\QueueFilter::class)]
class QueueFilterTest extends UnitTestCase
{
    final public const VALID_VALUE = 'finished';
    protected bool $resetSingletonInstances = true;

    #[\PHPUnit\Framework\Attributes\Test]
    public function defaultValueConstructor(): void
    {
        self::assertEquals('all', new QueueFilter());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function toStringWithValidValueReturnsOriginalValue(): void
    {
        $queueFilter = new QueueFilter(self::VALID_VALUE);
        self::assertEquals(self::VALID_VALUE, $queueFilter->__toString());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorThrowsException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        new QueueFilter('INVALID');
    }
}
