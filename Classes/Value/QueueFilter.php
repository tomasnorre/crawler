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

use Assert\Assert;

/**
 * @internal since v9.2.5
 */
class QueueFilter
{
    private string $queueFilter;

    public function __construct(string $queueFilter = 'all')
    {
        Assert::that($queueFilter)
            ->inArray(['all', 'pending', 'finished']);

        $this->queueFilter = $queueFilter;
    }

    public function __toString()
    {
        return $this->queueFilter;
    }
}
