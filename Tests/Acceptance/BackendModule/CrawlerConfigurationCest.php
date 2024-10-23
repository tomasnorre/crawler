<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Acceptance\BackendModule;

/*
 * (c) 2022-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

class CrawlerConfigurationCest
{
    // Implemented in Playwright for TYPO3 V13
    public function canCreateCrawlerConfiguration(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->click('[data-moduleroute-identifier="web_list"]');
        // Due to slow response time.
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->waitForElement('#typo3-pagetree-treeContainer', 120);
        $pageTree->openPath(['Welcome']);
        // Due to slow response time.
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->switchToContentFrame();
        $I->click('New record', '#t3-table-tx_crawler_configuration');
        $I->waitForText('Create new Crawler Configuration');
        $I->fillField('#EditDocumentController .form-control', 'Test Configuration');
        $I->click('Save', '.btn-toolbar');
        $I->dontSee('Error');
    }
}
