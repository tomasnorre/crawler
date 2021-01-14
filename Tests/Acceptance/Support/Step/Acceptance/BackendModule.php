<?php

declare(strict_types=1);

namespace Step\Acceptance;

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

class BackendModule extends \AcceptanceTester
{
    public function openCrawlerBackendModule(Admin $I, PageTree $pageTree): void
    {
        $I->click('#web_info');
        // Due to slow response time.
        $I->wait(30);
        $pageTree->openPath(['[1] Congratulations']);
        // Due to slow response time.
        $I->wait(30);
        $I->switchToContentFrame();
        $I->waitForText('Page information', 10);
    }

    public function openCrawlerBackendModuleStartCrawling(Admin $I, PageTree $pageTree): void
    {
        $this->openCrawlerBackendModule($I, $pageTree);
        $I->selectOption('SET[crawlaction]', 'start');
        $I->waitForText('Please select at least one configuration');
    }

    public function openCrawlerBackendModuleCrawlerLog(Admin $I, PageTree $pageTree): void
    {
        $this->openCrawlerBackendModule($I, $pageTree);
        $I->selectOption('SET[crawlaction]', 'log');
        $I->waitForText('Crawler log');
    }

    public function openCrawlerBackendModuleCrawlerMultiProcess(Admin $I, PageTree $pageTree): void
    {
        $this->openCrawlerBackendModule($I, $pageTree);
        $I->selectOption('SET[crawlaction]', 'multiprocess');
        $I->waitForText('CLI-Path');
    }

    /**
     * @noRector \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedParameterRector
     */
    public function addProcessOnMultiProcess(Admin $I, PageTree $pageTree): void
    {
        $I->click('Add process');
        $I->waitForText('New process has been started');
    }
}
