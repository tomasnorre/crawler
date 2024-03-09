<?php

declare(strict_types=1);

namespace Step\Acceptance;

/*
 * (c) 2021-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
    public function openCrawlerBackendModuleStartCrawling(Admin $I, PageTree $pageTree): void
    {
        $this->openCrawlerBackendModule($I, $pageTree);
        $I->selectOption('moduleMenu', 'Start');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->waitForText('Please select at least one configuration');
    }

    public function openCrawlerBackendModuleCrawlerLog(Admin $I, PageTree $pageTree): void
    {
        $this->openCrawlerBackendModule($I, $pageTree);
        $I->selectOption('moduleMenu', 'Log');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->waitForText('Crawler log');
    }

    public function openCrawlerBackendModuleCrawlerProcess(Admin $I, PageTree $pageTree): void
    {
        $this->openCrawlerBackendModule($I, $pageTree);
        $I->selectOption('moduleMenu', 'Process');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->waitForText('CLI-Path');
    }

    /**
     * @noRector \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedParameterRector
     */
    public function addProcessOnProcess(Admin $I, PageTree $pageTree): void
    {
        $I->click('Add process');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->waitForText('New process has been started');
    }
    private function openCrawlerBackendModule(Admin $I, PageTree $pageTree): void
    {
        $I->click('[data-moduleroute-identifier="web_site_crawler"]');
        // Due to slow response time.
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->waitForElement('#typo3-pagetree-treeContainer', 120);
        $pageTree->openPath(['Welcome']);
        // Due to slow response time.
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->switchToContentFrame();
        $I->waitForText('Crawl', 10);
    }
}
