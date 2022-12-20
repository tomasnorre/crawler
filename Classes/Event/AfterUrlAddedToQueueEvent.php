<?php

declare(strict_types=1);

namespace AOE\Crawler\Event;

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

/**
 * @internal since v12.0.0
 */
final class AfterUrlAddedToQueueEvent
{
    public function __construct(
        private readonly string $uid,
        private readonly array $fieldArray
    ) {
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function getFieldArray(): array
    {
        return $this->fieldArray;
    }
}
