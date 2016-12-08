<?php
return array(
    'tx_crawler_api' => 'AOE\\Crawler\\Api\\CrawlerApi',
    'tx_crawler_auth' => 'AOE\\Crawler\\Service\\AuthenticationService',
    'tx_crawler_scheduler_flush' => 'AOE\\Crawler\\Task\\FlushQueueTask',
    'tx_crawler_scheduler_im' => 'AOE\\Crawler\\Task\\CrawlerQueueTask',
    'tx_crawler_scheduler_crawlMultiProcess' => 'AOE\\Crawler\\Task\\CrawlMultiProcessTask',
    'tx_crawler_scheduler_crawl' => 'AOE\\Crawler\\Task\\CrawlerTask',
    'tx_crawler_tcafunc' => 'AOE\\Crawler\\Utility\\TcaUtility',
    'AOE\\Crawler\\Tasks\\ProcessCleanupTask' => 'AOE\\Crawler\\Task\\ProcessCleanupTask'
);
