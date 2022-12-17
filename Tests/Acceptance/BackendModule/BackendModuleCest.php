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
        $I->waitForText('CLI-Path', 15);
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
    public function seeCrawlerLogWithoutErrors(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
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

    public function crawlerUrlsContinueAndShowLog(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'default');
        $I->click('Crawl URLs');
        $I->waitForText('1 URLs submitted', 15);
        $I->click('Continue and show Log');
        $I->waitForText('Crawler log', 15);
        $I->waitForText('Welcome', 10);
        $I->waitForText('https://crawler-devbox.ddev.site/', 10);
        $I->waitForText('tx_indexedsearch_reindex', 10);
    }

    public function crawlerUrlsContinueAndShowLogCheckDepthDropdown(
        BackendModule $I,
        Admin $adminStep,
        PageTree $pageTree
    ): void {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleStartCrawling($adminStep, $pageTree);
        $I->selectOption('configurationSelection[]', 'default');
        $I->selectOption('crawlingDepth', '99');
        $I->click('Crawl URLs');
        $I->waitForText('URLs submitted', 15);
        $I->click('Continue and show Log');
        $I->waitForText('Crawler log', 15);
        $I->waitForText('Welcome', 10);
        $I->waitForText('https://crawler-devbox.ddev.site/', 10);
        $I->waitForText('tx_indexedsearch_reindex', 10);
        $I->selectOption('logDepth', '99');
        $I->waitForText('Page with Subpages', 10);
        $I->waitForText('https://crawler-devbox.ddev.site/page-with-subpages', 10);
        $I->waitForText('Access Restricted Page', 10);
        $I->waitForText('https://crawler-devbox.ddev.site/access-restricted-page', 10);
    }

    public function flushVisibleEntries(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        // Will test twice, but done to avoid duplicate code
        $this->crawlerUrlsContinueAndShowLogCheckDepthDropdown($I, $adminStep, $pageTree);
        $I->click('Flush entire queue');
        $I->switchToMainFrame();
        $I->seeInPopup('Are you sure?');
        $I->click('OK');
        $I->switchToContentFrame();
        $I->wait(3);
        $I->canSeeNumberOfElements('a.refreshLink', 0);
        $this->crawlerUrlsContinueAndShowLogCheckDepthDropdown($I, $adminStep, $pageTree);
        $I->canSeeNumberOfElements('a.refreshLink', 9);
        $I->selectOption('logDisplay', 'Finished');
        $I->canSeeNumberOfElements('a.refreshLink', 0);
        $I->click('Flush visible entries');
        $I->acceptPopup();
        $I->wait(3);
        $I->selectOption('logDisplay', 'All');
        $I->canSeeNumberOfElements('a.refreshLink', 9);
        $I->selectOption('logDisplay', 'Pending');
        $I->click('Flush visible entries');
        $I->acceptPopup();
        $I->wait(3);
        $I->canSeeNumberOfElements('a.refreshLink', 0);
    }

    public function CrawlerLogDisplayAndItemsPerPageDropdowns(
        BackendModule $I,
        Admin $adminStep,
        PageTree $pageTree
    ): void {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleCrawlerLog($adminStep, $pageTree);
        $I->selectOption('moduleMenu', 'Log');
        $I->waitForText('Crawler log', 15);

        $I->selectOption('logDisplay', 'Pending');
        $I->seeOptionIsSelected('logDisplay', 'Pending');
        # Check the dropdowns work individually
        $I->selectOption('logDisplay', 'Finished');
        $I->seeOptionIsSelected('logDisplay', 'Finished');
        $I->selectOption('itemsPerPage', 'limit to 5');
        $I->seeOptionIsSelected('itemsPerPage', 'limit to 5');
        $I->seeOptionIsSelected('logDisplay', 'Finished');

        # Check that the second dropdown selection keeps selected.
        $I->selectOption('itemsPerPage', 'no limit');
        $I->seeOptionIsSelected('itemsPerPage', 'no limit');
        $I->seeOptionIsSelected('logDisplay', 'Finished');
        $I->selectOption('logDisplay', 'Pending');
        $I->seeOptionIsSelected('itemsPerPage', 'no limit');
        $I->seeOptionIsSelected('logDisplay', 'Pending');
    }

    public function CrawlerLogResultLogAndFEVarsCheckboxes(BackendModule $I, Admin $adminStep, PageTree $pageTree): void
    {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleCrawlerLog($adminStep, $pageTree);

        # Check individually
        $this->resetCheckboxes($I);
        $I->checkOption('ShowResultLog');
        $I->seeCheckboxIsChecked('ShowResultLog');
        $I->dontSeeCheckboxIsChecked('ShowFeVars');

        $this->resetCheckboxes($I);
        $I->checkOption('ShowFeVars');
        $I->seeCheckboxIsChecked('ShowFeVars');
        $I->dontSeeCheckboxIsChecked('ShowResultLog');

        # Check in combination
        $this->resetCheckboxes($I);
        $I->checkOption('ShowResultLog');
        $I->checkOption('ShowFeVars');
        $I->seeCheckboxIsChecked('ShowFeVars');
        $I->seeCheckboxIsChecked('ShowResultLog');

        $this->resetCheckboxes($I);
        $I->checkOption('ShowResultLog');
        $I->checkOption('ShowFeVars');
        $I->uncheckOption('ShowFeVars');
        $I->seeCheckboxIsChecked('ShowResultLog');
        $I->dontSeeCheckboxIsChecked('ShowFeVars');

        $this->resetCheckboxes($I);
        $I->checkOption('ShowResultLog');
        $I->checkOption('ShowFeVars');
        $I->uncheckOption('ShowResultLog');
        $I->seeCheckboxIsChecked('ShowFeVars');
        $I->dontSeeCheckboxIsChecked('ShowResultLog');
    }

    public function CrawlerLogDropDownAndCheckboxesCombined(
        BackendModule $I,
        Admin $adminStep,
        PageTree $pageTree
    ): void {
        $adminStep->loginAsAdmin();
        $I->openCrawlerBackendModuleCrawlerLog($adminStep, $pageTree);

        // Check individually
        $this->resetCheckboxes($I);
        $I->checkOption('ShowResultLog');
        $I->seeCheckboxIsChecked('ShowResultLog');
        $I->dontSeeCheckboxIsChecked('ShowFeVars');

        $I->selectOption('itemsPerPage', 'no limit');
        $I->seeOptionIsSelected('itemsPerPage', 'no limit');
        $I->selectOption('logDisplay', 'Pending');
        $I->seeOptionIsSelected('logDisplay', 'Pending');
        # The checkboxes aren't allowed to change
        $I->seeCheckboxIsChecked('ShowResultLog');
        $I->dontSeeCheckboxIsChecked('ShowFeVars');

        // Reversed case, dropdowns are set first, and then checkboxes used.
        $this->resetCheckboxes($I);
        $I->selectOption('itemsPerPage', 'no limit');
        $I->seeOptionIsSelected('itemsPerPage', 'no limit');
        $I->selectOption('logDisplay', 'Pending');
        $I->seeOptionIsSelected('logDisplay', 'Pending');

        $I->checkOption('ShowResultLog');
        $I->seeCheckboxIsChecked('ShowResultLog');
        $I->dontSeeCheckboxIsChecked('ShowFeVars');
        $I->checkOption('ShowFeVars');
        $I->seeCheckboxIsChecked('ShowFeVars');
        $I->seeOptionIsSelected('logDisplay', 'Pending');
        $I->seeOptionIsSelected('itemsPerPage', 'no limit');

        // Include logDepth
        $this->resetCheckboxes($I);
        $I->selectOption('itemsPerPage', 'no limit');
        $I->seeOptionIsSelected('itemsPerPage', 'no limit');
        $I->selectOption('logDisplay', 'Pending');
        $I->seeOptionIsSelected('logDisplay', 'Pending');
        $I->selectOption('logDepth', '4 levels');

        $I->checkOption('ShowResultLog');
        $I->seeCheckboxIsChecked('ShowResultLog');
        $I->dontSeeCheckboxIsChecked('ShowFeVars');
        $I->checkOption('ShowFeVars');
        $I->seeCheckboxIsChecked('ShowFeVars');
        $I->seeOptionIsSelected('logDisplay', 'Pending');
        $I->seeOptionIsSelected('itemsPerPage', 'no limit');
        $I->seeOptionIsSelected('logDepth', '4 levels');
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

    private function resetCheckboxes(BackendModule $I): void
    {
        $I->uncheckOption('ShowResultLog');
        $I->uncheckOption('ShowFeVars');
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
