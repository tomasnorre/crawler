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

namespace AOE\Crawler\Event;

final class AfterUrlCrawledEvent
{
    private string $url;
    private array $result;

    public function __construct(string $url, array $result)
    {
        $this->url = $url;
        $this->result = $result;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getResult(): array
    {
        return $this->result;
    }
}
