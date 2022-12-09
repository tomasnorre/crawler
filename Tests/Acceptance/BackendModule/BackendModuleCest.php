<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Acceptance\BackendModule;

/*
 * (c) 2005-2021 AOE GmbH <dev@aoe.com>
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
use Step\Acceptance\BackendModule;

class BackendModuleCest
{
    public function canSeeLoginMask(Admin $I): void
    {
        $I->amOnPage('/typo3');
        $I->waitForText('Login', 5);
    }

    public function signInSuccessfully(Admin $I): void
    {
        $I->loginAsAdmin();
    }

    public function canSeeCrawlerModule(Admin $I): void
    {
        $I->loginAsAdmin();
        $I->canSee('Crawler', '#web_site_crawler');
    }

    public function canSelectCrawlerModuleStartCrawling(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
    }

    public function canSelectCrawlerModuleCrawlerLog(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleCrawlerLog($adminStep, $pageTree);
    }

    public function canSelectCrawlerModuleProcess(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleCrawlerProcess($adminStep, $pageTree);
    }

    public function updateUrlButton(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'default');
        $I->click('Update');
        $I->waitForText('Count: 1', 15);
    }

    /**
     * Ensure that Crawler Configurations with Exclude pages set to: e.g. 6+3 is working
     * https://github.com/AOEpeople/crawler/issues/777
     */
    public function CrawlerConfigurationWithExcludePageSixPlusThree(
        BackendModule $I,
        Admin $adminStep,
        PageTree $pageTree
    ): void {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'excludepages-6-plus-3');
        $I->click('Update');
        $I->dontSee('TypeError');
        $I->waitForText('Count: 1', 15);
    }

    public function EnsureNoUserGroupsAndNoProcInstAreDisplayed(
        BackendModule $I,
        Admin $adminStep,
        PageTree $pageTree
    ): void {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'excludepages-6-plus-3');
        $I->click('Update');
        $I->dontSee('User Groups: ');
        $I->dontSee('ProcInstr:: ');
        $I->waitForText('Count: 1', 15);
    }

    public function updateUrlButtonSetDepth(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'default');
        $I->selectOption('crawlingDepth', 99);
        $I->click('Update');
        $I->waitForElementVisible('.table-striped', 15);
    }

    public function crawlerAddProcess(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $this->addQueueEntry($I, $adminStep, $pageTree);

        // Navigate to Process View
        $I->selectOption('moduleMenu', 'Process');
        $I->waitForText('CLI-Path',15);
        $I->addProcessOnProcess($adminStep, $pageTree);
    }

    public function processSuccessful(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $this->addQueueEntry($I, $adminStep, $pageTree);
        $this->addProcess($I);
        $I->click('Show finished and terminated processes');
        $I->waitForText('Process completed successfully', 60);
        $I->dontSee('Process was cancelled');
    }

    /**
     * Ensures that Result logs are writing correctly
     * https://github.com/tomasnorre/crawler/issues/826
     */
    public function seeCrawlerLogWithOutErrors(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $this->addQueueEntry($I, $adminStep, $pageTree);
        $this->addProcess($I);
        $I->click('Show finished and terminated processes');
        $I->waitForText('Process completed successfully', 60);
        // Check Result
        //$I->selectOption('moduleMenu', 'log');
        //$I->dontSee('Content index does not exists in requestContent');
    }

    public function manualTriggerCrawlerFromLog(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $this->addQueueEntry($I, $adminStep, $pageTree);
        $I->selectOption('moduleMenu', 'Log');
        // Click on "refresh" for given record
        $I->click('.refreshLink');
        $I->dontSee('Whoops, looks like something went wrong.');
        $I->waitForText('OK', 15);
    }

    public function crawlerUlsContinueAndShowLog(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'default');
        $I->click('Crawl URLs');
        $I->waitForText('1 URLs submitted', 15);
        $I->click('Continue and show Log');
        $I->waitForText('Crawler Log', 15);
    }

    public function checkSelections(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);

        // Action
        $I->see('Start');
        $I->see('Log');
        $I->see('Process');

        // Configurations
        $I->see('default');
        $I->see('excludepages-6-plus-3');

        // Depth
        $I->see('This page');
        $I->see('1 level');
        $I->see('2 levels');
        $I->see('3 levels');
        $I->see('4 levels');
        $I->see('Infinite');

        // Scheduled
        $I->see('Now');
        $I->see('Midnight');
        $I->see('4 AM');
    }

    /**
     * @throws \Exception
     */
    private function addQueueEntry(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'default');
        $I->click('Crawl URLs');
        $I->waitForText('1 URLs submitted', 15);
    }

    /**
     * @throws \Exception
     */
    private function addProcess(BackendModule $I): void
    {
        $I->selectOption('moduleMenu', 'Process');
        $I->waitForText('CLI-Path', 15);
        $I->click('Add process');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->waitForText('New process has been started');
    }
}
