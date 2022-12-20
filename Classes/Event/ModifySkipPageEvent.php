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
final class ModifySkipPageEvent
{
    private bool|string $skipped = false;

    public function __construct(
        private readonly array $pageRow
    ) {
    }

    public function isSkipped(): false|string
    {
        return $this->skipped;
    }

    public function setSkipped(false|string $skipped): void
    {
        $this->skipped = $skipped;
    }

    public function getPageRow(): array
    {
        return $this->pageRow;
    }
}
