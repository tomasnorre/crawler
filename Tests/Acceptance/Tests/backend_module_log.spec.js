import {test, expect} from '@playwright/test';
import * as helpers from './helpers';

test('Can crawl manually in log module, and keep selected log depth', { tag: ['@v13'] },async ({page}) => {
    await helpers.loginBackend(page)
    await helpers.addQueueEntries(page, 'default', '99');
    await helpers.openCrawlerModuleCrawlerLog(page)
    await page.locator('iframe[name="list_frame"]').contentFrame().getByText('Crawler log').isVisible();
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('.refreshLink').first().isVisible();
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="logDepth"]').selectOption('3');
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="logDepth"]')).toHaveValue('3');
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('.refreshLink').first().click();
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="logDepth"]').isVisible();
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="logDepth"]')).valueOf('3');
});
