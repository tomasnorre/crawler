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

class BackendModuleCest
{
    public function canSeeLoginMask(Admin $I): void
    {
        $I->amOnPage('/');
        $I->waitForText('Login', '15');
    }

    public function signInSuccessfully(Admin $I): void
    {
        $I->loginAsAdmin();
    }

    public function canSeeInfoModule(Admin $I): void
    {
        $I->loginAsAdmin();
        $I->canSee('Info', '#web_info');
    }

    public function canSelectInfoModuleStartCrawling(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
    }

    public function canSelectInfoModuleCrawlerLog(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModuleCrawlerLog($adminStep, $pageTree);
    }

    public function canSelectInfoModuleMultiProcess(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModuleCrawlerMultiProcess($adminStep, $pageTree);
    }

    public function updateUrlButton(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'default');
        $I->click('Update');
        $I->waitForText('Count: 1', 15);
    }

    public function updateUrlButtonSetDepth(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'default');
        $I->selectOption('SET[depth]', 99);
        $I->click('Update');
        $I->waitForElementVisible('.table-striped', 15);
    }
}
