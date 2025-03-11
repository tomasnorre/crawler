<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use AOE\Crawler\Event\InvokeQueueChangeEvent;

final class InvokeQueueChangeEventListener
{
    public function __invoke(InvokeQueueChangeEvent $invokeQueueChangeEvent)
    {
        $reason = $invokeQueueChangeEvent->getReasonText();
        // You can implement different logic based on reason, GUI or CLI
    }
}
