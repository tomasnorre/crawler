// @ts-check
const {test, expect} = require('@playwright/test');

test('Can see homepage', async ({page}) => {
    await page.goto('/');
    await expect(page.getByRole('list').first()).toContainText('Search');
    await expect(page.getByRole('list').first()).toContainText('Login');
});

test('Can see news page', async ({page}) => {
    await page.goto('/news');
    await expect(page.getByRole('document')).toContainText('No news available.');
});

test('Can see search page and search for Tomasnorre', async ({page}) => {
    await page.goto('/search');
    await expect(page.getByRole('document')).toContainText('Search');
    await page.fill('#tx-indexedsearch-searchbox-sword', 'tomasnorre');
    await page.click('#tx-indexedsearch-searchbox-button-submit')
    await expect(page.getByRole('document')).toContainText('Displaying results 1 to 1');
});
