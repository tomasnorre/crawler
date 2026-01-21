import { test, expect } from '@playwright/test';
import * as helpers from './helpers';

test('Able to create and save crawler configuration v13', { tag: '@v13' }, async ({ page}) => {
    await helpers.loginBackend(page)
    await page.getByTitle('List', { exact: true }).click();
    await page.locator('div.node:nth-child(2)').click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('link', { name: 'New Crawler Configuration' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('h1')).toContainText('Create new Crawler Configuration on page "Welcome"');
    //await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').fill('Test Configuration');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Save' }).click();
});

test('Able to create and save crawler configuration v14', { tag: '@v14' }, async ({ page}) => {
    test.slow();
    await helpers.loginBackend(page)
    await page.getByTitle('Records', { exact: true }).click();
    await page.locator('div').filter({ hasText: /^Welcome$/ }).first().click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'New Crawler Configuration' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('h1')).toContainText('Create new Crawler Configuration on page "Welcome"');
    //await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').fill('Test Configuration');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Save' }).click();
});
