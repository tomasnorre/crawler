<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use Psr\Clock\ClockInterface;

/**
 * A clock that always returns the same time, for tests.
 * Like the TYPO3 SystemClock, the time is in the default timezone.
 */
final readonly class FrozenClock implements ClockInterface
{
    private \DateTimeImmutable $now;

    /**
     * @param int|string $now A Unix timestamp, or a date and time like "2026-11-16 15:30:00"
     */
    public function __construct(int|string $now)
    {
        $this->now = is_int($now)
            ? (new \DateTimeImmutable('@' . $now))->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            : new \DateTimeImmutable($now);
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
