import { test, expect } from '@playwright/test';
import * as helpers from './helpers';

test('Can Flush all processes', async ({ page }) => {
    await helpers.openCrawlerModule(page)
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'Flush all processes' }).click();
    // Todo: Check that rows before and after.
});

test('Can disable and enable crawler', async ({ page }) => {
    await helpers.openCrawlerModuleCrawlerProcesses(page);

    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'Stop all processes and' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('body')).toContainText('Enable crawling');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'Enable crawling' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('body')).toContainText('Stop all processes and disable crawling');
});
