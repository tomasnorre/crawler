<?php

declare(strict_types=1);

namespace AOE\Crawler\EventListener;

use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;

/**
 * Disable frontend cache when the HTTP request has crawler parameters,
 * so that page indexing hooks are triggered.
 */
class ShouldUseCachedPageDataIfAvailableEventListener
{
    public function __invoke(ShouldUseCachedPageDataIfAvailableEvent $event): void
    {
        if ($event->getRequest()->getAttribute('tx_crawler') === null) {
            return;
        }
        $event->setShouldUseCachedPageData(false);
    }
}
