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
        $I->canSee('Login');
    }

    public function signInSuccessfully(Admin $I): void
    {
        $I->loginAsAdmin();
    }

    public function canSeeInfoModule(Admin $I): void
    {
        $I->loginAsAdmin();
        $I->canSee('crawler-devbox');
        $I->canSee('Info', '#web_info');
    }

    public function canSelectInfoModule(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModule($adminStep, $pageTree);
        $I->switchToContentFrame();
        $I->see('Please select at least one configuration');
    }

    public function updateUrlButton(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModule($adminStep, $pageTree);
        $I->switchToContentFrame();
        $I->selectOption('configurationSelection[]', 'default');
        $I->click('Update');
        $I->waitForText('Count: 1');
    }

    public function updateUrlButtonSetDepth(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModule($adminStep, $pageTree);
        $I->switchToContentFrame();
        $I->selectOption('configurationSelection[]', 'default');
        $I->selectOption('SET[depth]', 99);
        $I->click('Update');
        $I->waitForText('Count: 43');
    }
}
