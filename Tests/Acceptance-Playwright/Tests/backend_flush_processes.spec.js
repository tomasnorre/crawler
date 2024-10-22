import { test, expect } from '@playwright/test';
import * as helpers from './helpers';

test('Can Flush all processes', async ({ page }) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleStartCrawling(page)
    await page.getByRole('treeitem', { name: 'Welcome' }).locator('div').nth(2).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="configurationSelection[]"]').selectOption('default');
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="crawlingDepth"]').selectOption('99');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Crawl URLs' }).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await helpers.openCrawlerModuleCrawlerProcesses(page)
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'Add process' }).click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'Add process' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('#processes tbody tr')).toHaveCount(2)
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'Flush all processes' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('#processes tbody tr')).toHaveCount(0)
});

test('Can disable and enable crawler', async ({ page }) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleCrawlerProcesses(page);
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'Stop all processes and' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('body')).toContainText('Enable crawling');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'Enable crawling' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('body')).toContainText('Stop all processes and disable crawling');
});
