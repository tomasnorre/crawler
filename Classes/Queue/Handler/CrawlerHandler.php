<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue\Handler;

use AOE\Crawler\Queue\Message\CrawlerMessage;

final class CrawlerHandler
{
    public function __invoke(CrawlerMessage $crawlerMessage)
    {
        error_log($crawlerMessage->content, 3, '/tmp/tomas.log');
    }
}
