<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue\Message;

final class CrawlerAddPageToQueueMessage {

    public function __construct(
        public readonly int $pageId,
    ){}
}
