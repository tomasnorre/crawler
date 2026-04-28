import {expect} from "@playwright/test";

export async function loginBackend(page) {
    await page.goto('/typo3/');
    await page.fill('input#t3-username', 'admin');
    await page.fill('input#t3-password', 'password');
    await page.click('button#t3-login-submit')
}

export async function openCrawlerModule(page) {
    await page.getByText('Crawler module').click();
}

export async function openCrawlerModuleCrawlerProcesses(page) {
    await page.goto('/typo3/module/page/crawler/process?id=1');
}

export async function openCrawlerModuleStartCrawling(page) {
    await page.goto('/typo3/module/page/crawler/start?id=1');
}

export async function openCrawlerModuleCrawlerLog(page) {
    await page.goto('/typo3/module/page/crawler/log?id=1');
}

export async function addQueueEntries(page, config, depth = '0') {
    await openCrawlerModuleStartCrawling(page)
    await page.locator('div.node:nth-child(2)').click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="configurationSelection[]"]').selectOption(config);
    await page.locator('iframe[name="list_frame"]').contentFrame().locator('select[name="crawlingDepth"]').selectOption(depth);
    await page.locator('iframe[name="list_frame"]').contentFrame().getByRole('button', {name: 'Crawl URLs'}).click();
    await expect(page.locator('#nprogress')).toHaveCount(0);
}

