export async function loginBackend(page) {
    await page.goto('/typo3/');
    await page.fill('input#t3-username', 'admin');
    await page.fill('input#t3-password', 'password');
    await page.click('button#t3-login-submit')
}

export async function openCrawlerModule(page) {
    await page.getByText('ext_icon_crawler Crawler').click();
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

