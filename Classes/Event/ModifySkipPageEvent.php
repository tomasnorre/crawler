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

final class ModifySkipPageEvent
{
    private array $pageRow;

    private bool|string $skipped = false;

    public function __construct(array $pageRow)
    {
        $this->pageRow = $pageRow;
    }

    /**
     * @return false|string
     */
    public function isSkipped()
    {
        return $this->skipped;
    }

    /**
     * @param false|string $skipped
     */
    public function setSkipped($skipped): void
    {
        $this->skipped = $skipped;
    }

    public function getPageRow(): array
    {
        return $this->pageRow;
    }
}
