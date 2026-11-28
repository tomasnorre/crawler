<?php

declare(strict_types=1);

namespace AOE\Crawler\CrawlStrategy\Interface;

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

use AOE\Crawler\Value\CrawlResult;
use Psr\Http\Message\UriInterface;

/**
 * @internal since v12.0.0
 */
interface CrawlStrategyInterface
{
    /**
     * Fetch the given URL and return the crawler data the page reports, or the error
     */
    public function fetchUrlContents(UriInterface $url, string $crawlerId): CrawlResult;
}
