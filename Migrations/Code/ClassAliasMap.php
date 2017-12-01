<?php
return [
    'tx_crawler_scheduler_flush' => \AOE\Crawler\Task\FlushQueueTask::class,
    'tx_crawler_scheduler_im' => \AOE\Crawler\Task\CrawlerQueueTask::class,
    'tx_crawler_scheduler_crawlMultiProcess' => \AOE\Crawler\Task\CrawlMultiProcessTask::class,
    'tx_crawler_scheduler_crawl' => \AOE\Crawler\Task\CrawlerTask::class,
    'AOE\Crawler\Tasks\ProcessCleanupTask' => \AOE\Crawler\Task\ProcessCleanupTask::class
];
