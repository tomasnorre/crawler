<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue\Handler;

use AOE\Crawler\Queue\Message\CrawlerAddPageToQueueMessage;

final class CrawlerAddPageToQueue
{
    public function __invoke(CrawlerAddPageToQueueMessage $message): void
    {

    }
}
