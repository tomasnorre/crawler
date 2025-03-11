<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Hooks\Crawler;

use AOE\Crawler\Controller\CrawlerController;

class PageVeto
{
    public function excludePage(array &$params, CrawlerController $controller)
    {
        if ($params['pageRow']['uid'] === 42) {
            return 'Page with uid "42" is excluded by page veto hook';
        }

        return false;
    }
}
