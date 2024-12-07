import { test, expect } from '@playwright/test';
import * as helpers from './helpers';

test('Update URL button', async ({ page }) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleStartCrawling(page)
    await page.getByRole('treeitem', { name: 'Welcome' }).locator('div').nth(2).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="configurationSelection[]"]').selectOption('default');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Update' }).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('Count')).toContainText('Count: 1');
});

test('CrawlerConfigurationWithExcludePageSixPlusThree', async ({ page }) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleStartCrawling(page)
    await page.getByRole('treeitem', { name: 'Welcome' }).locator('div').nth(2).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="configurationSelection[]"]').selectOption('excludepages-6-plus-3');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Update' }).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByText('Count')).toContainText('Count: 1');
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByRole('document')).not.toContainText('TypeError');
});

test('UpdateUrlButtonSetDepth', async ({ page }) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleStartCrawling(page)
    await page.getByRole('treeitem', { name: 'Welcome' }).locator('div').nth(2).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="configurationSelection[]"]').selectOption('default');
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="crawlingDepth"]').selectOption('99');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Update' }).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await page.locator('css=table.table-striped').isVisible()
    // Todo: Add this check back, when starting to fix
    //await expect(page.locator('iframe[name="list_frame"]').contentFrame().getByRole('document')).not.toContainText('PHP Warning');
});
