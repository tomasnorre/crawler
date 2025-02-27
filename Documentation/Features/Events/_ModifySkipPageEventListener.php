<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use AOE\Crawler\Event\ModifySkipPageEvent;

final class ModifySkipPageEventListener
{
    public function __invoke(ModifySkipPageEvent $modifySkipPageEvent)
    {
        if ($modifySkipPageEvent->getPageRow()['uid'] === 42) {
            $modifySkipPageEvent->setSkipped('Page with uid "42" is excluded by ModifySkipPageEvent');
        }
    }
}
