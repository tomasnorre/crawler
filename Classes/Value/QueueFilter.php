<?php

declare(strict_types=1);

namespace AOE\Crawler\Value;

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

/**
 * @internal since v9.2.5
 */
class QueueFilter implements \Stringable
{
    private const VALID_FILTERS = ['all', 'pending', 'finished'];

    private readonly string $queueFilter;

    public function __construct(string $queueFilter = 'all')
    {
        if (!in_array($queueFilter, self::VALID_FILTERS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Queue filter "%s" is invalid, use one of: %s',
                    $queueFilter,
                    implode(', ', self::VALID_FILTERS)
                ),
                1794663700
            );
        }

        $this->queueFilter = $queueFilter;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->queueFilter;
    }
}
