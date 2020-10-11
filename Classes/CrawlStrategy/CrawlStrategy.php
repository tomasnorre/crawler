<?php

declare(strict_types=1);

namespace AOE\Crawler\CrawlStrategy;

use Psr\Http\Message\UriInterface;

interface CrawlStrategy
{
    public function fetchUrlContents(UriInterface $url, string $crawlerId);
}
