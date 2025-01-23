// @ts-check
const {test, expect} = require('@playwright/test');
import * as helpers from './helpers';

test('Login page has title', async ({page}) => {
    await page.goto('/typo3/');
    await expect(page).toHaveTitle(/TYPO3 CMS Login/);
});

test('Can Login', async ({page}) => {
    await helpers.loginBackend(page);
});
