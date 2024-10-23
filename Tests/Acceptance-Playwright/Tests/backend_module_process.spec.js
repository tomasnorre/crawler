import {test, expect} from '@playwright/test';
import * as helpers from './helpers';

async function addQueueEntries(page, config, depth = '0') {
    await helpers.openCrawlerModuleStartCrawling(page)
    await page.getByRole('treeitem', {name: 'Welcome'}).locator('div').nth(2).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="configurationSelection[]"]').selectOption(config);
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="crawlingDepth"]').selectOption(depth);
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', {name: 'Crawl URLs'}).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
}

test('Can Flush all processes', async ({page}) => {
    await helpers.loginBackend(page)
    await addQueueEntries(page, 'default', '99');
    await helpers.openCrawlerModuleCrawlerProcesses(page)
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', {name: 'Add process'}).click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', {name: 'Add process'}).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('#processes tbody tr')).toHaveCount(2)
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', {name: 'Flush all processes'}).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('#processes tbody tr')).toHaveCount(0)
});

test('Can disable and enable crawler', async ({page}) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleCrawlerProcesses(page);
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', {name: 'Stop all processes and'}).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('body')).toContainText('Enable crawling');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', {name: 'Enable crawling'}).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('body')).toContainText('Stop all processes and disable crawling');
});

test('Can add process', async ({page}) => {
    await helpers.loginBackend(page)
    await addQueueEntries(page, 'default')
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('URLs submitted')).toContainText('1 URLs submitted');
    await helpers.openCrawlerModuleCrawlerProcesses(page);
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('CLI-Path')).toContainText('CLI-Path');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', {name: 'Add process'}).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('New process has been started')).toContainText('New process has been started');
});

test('Process successful', async ({page}) => {
    await helpers.loginBackend(page)
    await addQueueEntries(page, 'default')
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('URLs submitted')).toContainText('1 URLs submitted');
    await helpers.openCrawlerModuleCrawlerProcesses(page);
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('CLI-Path')).toContainText('CLI-Path');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', {name: 'Add process'}).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('New process has been started')).toContainText('New process has been started');

    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', {name: 'Show finished and terminated processes'}).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('Process completed successfully')).toContainText('Process completed successfully');
});
