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
/*
test('Able to create and save crawler configuration v14', { tag: '@v14' }, async ({ page}) => {
    await helpers.loginBackend(page)
    await page.getByTitle('Records', { exact: true }).click();
    await page.locator('div').filter({ hasText: /^Welcome$/ }).first().click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'New Crawler Configuration' }).click();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('h1')).toContainText('Create new Crawler Configuration on page "Welcome"');
    //await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').click();
    await page.locator('iframe[name="list_frame"]').contentFrame().getByLabel('Name').fill('Test Configuration');
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', { name: 'Save' }).click();
});
*/

test('Able to create and save crawler configuration v14', { tag: '@v14' }, async ({ page }) => {
    await helpers.loginBackend(page);


    page.on('console', msg =>
        console.log('[console]', msg.type(), msg.text())
    );

    page.on('pageerror', err =>
        console.log('[pageerror]', err.message)
    );

    page.on('requestfailed', req =>
        console.log('[requestfailed]', req.url(), req.failure()?.errorText)
    );

    page.on('response', res => {
        if (res.status() >= 400) {
            console.log('[response]', res.status(), res.url());
        }
    });

    // Ensure backend navigation is ready
    await page.getByTitle('Records', { exact: true }).waitFor();

    await page.getByTitle('Records', { exact: true }).click();
    await page.locator('div').filter({ hasText: /^Welcome$/ }).first().click();

    const listFrameLocator = page.locator('iframe[name="list_frame"]');
    await listFrameLocator.waitFor({ state: 'attached' });

    const listFrame = await listFrameLocator.contentFrame();
    if (!listFrame) throw new Error('list_frame not available');

    await listFrame.getByRole('button', { name: 'New Crawler Configuration' }).waitFor();
    await listFrame.getByRole('button', { name: 'New Crawler Configuration' }).click();

    await expect(
        listFrame.locator('h1')
    ).toContainText('Create new Crawler Configuration on page "Welcome"');

    await listFrame.getByLabel('Name').fill('Test Configuration');
    await listFrame.getByRole('button', { name: 'Save' }).click();
});

test('backend loads without interaction', { tag: '@v14' },async ({ page }) => {
    await helpers.loginBackend(page);
    await page.waitForSelector('iframe[name="nav_frame"]', { timeout: 30_000 });
    await page.waitForSelector('iframe[name="list_frame"]', { timeout: 30_000 });
});


