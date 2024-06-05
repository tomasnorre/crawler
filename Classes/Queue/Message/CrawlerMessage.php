<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue\Message;

final class CrawlerMessage
{
    public function __construct(
        public readonly string $content,
        public readonly int $id,
    )
    {
        error_log('new message is created' . chr(10), 3, '/tmp/tomas.log');
    }
}
