<?php
declare(strict_types=1);
namespace MyVendor\MyExtension\EventListener;

use AOE\Crawler\Event\AfterQueueItemAddedEvent;

final class AfterQueueItemAddedEventListener
{
    public function __invoke(AfterQueueItemAddedEvent $afterQueueItemAddedEvent)
    {
        // Implement your wanted logic, you have the `$queueId` and `$fieldArray` information
    }
}
