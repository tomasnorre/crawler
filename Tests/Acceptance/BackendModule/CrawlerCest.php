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
    // Implemented in Playwright for TYPO3 V13
    public function canDisableAndEnableCrawler(BackendModule $I, Admin $adminStep, PageTreettomasnore $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleCrawlerProcess($adminStep, $pageTree);
        $I->canSee('Stop all processes and disable crawling');
        $I->click('Stop all processes and disable crawling');
        $I->canSee('Enable crawling');
        $I->click('Enable crawling');
        $I->canSee('Stop all processes and disable crawling');
    }

    // Implemented in Playwright for TYPO3 V13
    public function canSeeFlushAllProcesses(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleCrawlerProcess($adminStep, $pageTree);
        $I->canSee('Flush all processes');
        $I->click('Show finished and terminated processes');
        $I->canSeeNumberOfElements('#processes tbody tr', 3);
        $I->click('Flush all processes');
        $I->click('Show finished and terminated processes');
        $I->canSeeNumberOfElements('#processes tbody tr', 0);
    }

}
