<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use AOE\Crawler\Event\AfterUrlCrawledEvent;

final class AfterUrlCrawledEventListener
{
    public function __invoke(AfterUrlCrawledEvent $afterUrlCrawledEvent): void
    {
        // The crawl result, fx. getStatus(), getErrorLog(), getLog() or getVars()
        if ($afterUrlCrawledEvent->getResult()->getErrorLog() === []) {
            // VarnishBanUrl($afterUrlCrawledEvent->getUrl());
        }
    }
}
