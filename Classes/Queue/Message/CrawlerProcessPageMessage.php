<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue\Message;

final class CrawlerProcessPageMessage
{
    public function __construct(
        private readonly int $pageId
    ){}
}
