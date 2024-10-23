import { test, expect } from '@playwright/test';
import * as helpers from './helpers';

test('test', async ({ page }) => {
    await helpers.loginBackend(page)
    await page.getByTitle('List', { exact: true }).click();
    await page.getByRole('treeitem', { name: 'Welcome' }).locator('div').nth(2).click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'New Crawler Configuration' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('h1')).toContainText('Create new Crawler Configuration on page "Welcome"');
    //await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').fill('Test Configuration');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Save' }).click();
});
