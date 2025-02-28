<?php
declare(strict_types=1);
namespace MyVendor\MyExtension\EventListener;

use AOE\Crawler\Event\AfterUrlAddedToQueueEvent;

final class AfterUrlAddedToQueueEventListener
{
    public function __invoke(AfterUrlAddedToQueueEvent $afterUrlAddedToQueueEvent): void
    {
        // Implement your wanted logic, you have the `$uid` and `$fieldArray` information
    }
}
