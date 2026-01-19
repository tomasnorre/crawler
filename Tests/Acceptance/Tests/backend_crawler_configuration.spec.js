import { test, expect } from '@playwright/test';
import * as helpers from './helpers';

test('Enable to create and save crawler configuration', { tag: '@v13' }, async ({ page}) => {
    await helpers.loginBackend(page)
    await page.getByTitle('List', { exact: true }).click();
    await page.locator('div.node:nth-child(2)').click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'New Crawler Configuration' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('h1')).toContainText('Create new Crawler Configuration on page "Welcome"');
    //await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').fill('Test Configuration');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Save' }).click();
});
