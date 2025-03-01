<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use AOE\Crawler\Event\BeforeQueueItemAddedEvent;

final class BeforeQueueItemAddedEventListener
{
    public function __invoke(BeforeQueueItemAddedEvent $beforeQueueItemAddedEvent)
    {
        // Implement your wanted logic, you have the `$queueId` and `$queueRecord` information
    }
}
