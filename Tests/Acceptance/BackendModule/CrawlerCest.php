<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Acceptance\BackendModule;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AOE\Crawler\Tests\Acceptance\Support\Helper\PageTree;
use AOE\Crawler\Tests\Acceptance\Support\Step\Acceptance\Admin;
use Step\Acceptance\BackendModule;

class CrawlerCest
{
    public function canDisableAndEnableCrawler(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleCrawlerMultiProcess($adminStep, $pageTree);
        $I->waitForText('Stop all processes and disable crawling', 5);
        $I->click('Stop all processes and disable crawling');
        $I->waitForText('Enable crawling', 5);
        $I->click('Enable crawling');
        $I->waitForText('Stop all processes and disable crawling', 5);
    }
}
