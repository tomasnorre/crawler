<?php
return [
    'tx_crawler_api' => \AOE\Crawler\Api\CrawlerApi::class,
    'tx_crawler_auth' => \AOE\Crawler\Service\AuthenticationService::class,
    'tx_crawler_domain_lib_abstract_repository' => \AOE\Crawler\Domain\Repository\AbstractRepository::class,
    'tx_crawler_scheduler_flush' => \AOE\Crawler\Task\FlushQueueTask::class,
    'tx_crawler_scheduler_im' => \AOE\Crawler\Task\CrawlerQueueTask::class,
    'tx_crawler_scheduler_crawlMultiProcess' => \AOE\Crawler\Task\CrawlMultiProcessTask::class,
    'tx_crawler_scheduler_crawl' => \AOE\Crawler\Task\CrawlerTask::class,
    'AOE\Crawler\Tasks\ProcessCleanupTask' => \AOE\Crawler\Task\ProcessCleanupTask::class,
    'tx_crawler_tcafunc' => \AOE\Crawler\Utility\TcaUtility::class,
];
