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
    await helpers.loginBackend(page)
    await page.getByTitle('Records', { exact: true }).click();
    await page.waitForLoadState('networkidle');
    await page.locator('div').filter({ hasText: /^Welcome$/ }).first().click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'New Crawler Configuration' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('h1')).toContainText('Create new Crawler Configuration on page "Welcome"');
    //await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').fill('Test Configuration');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Save' }).click();
});

test('Able to create and save crawler configuration v14 v2', { tag: '@v14' }, async ({ page }) => {
    await helpers.loginBackend(page);

    // 1. Click Records and wait for the URL to stabilize
    await page.getByTitle('Records', { exact: true }).click();

    // 2. Ensure the "Navigation loading error" toast isn't visible
    // This acts as a 'guard'—if it appears, the test fails immediately with a clear reason
    await expect(page.locator('.typo3-module-menu')).toBeVisible();
    await expect(page.getByText('Navigation loading error')).not.toBeVisible();

    // 3. Click "Welcome" in the tree (Wait for it to be visible first)
    const welcomeNode = page.locator('div').filter({ hasText: /^Welcome$/ }).first();
    await welcomeNode.waitFor({ state: 'visible' });
    await welcomeNode.click();

    // 4. Use the modern frameLocator API
    const listFrame = page.frameLocator('iframe[name="list_frame"]');

    const newConfigBtn = listFrame.getByRole('button', { name: 'New Crawler Configuration' });
    await newConfigBtn.click();

    await expect(listFrame.locator('h1')).toContainText('Create new Crawler Configuration on page "Welcome"');

    await listFrame.getByLabel('Name').fill('Test Configuration');
    await listFrame.getByRole('button', { name: 'Save' }).click();
});
