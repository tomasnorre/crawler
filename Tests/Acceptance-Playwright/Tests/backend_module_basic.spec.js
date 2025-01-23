// @ts-check
const {test, expect} = require('@playwright/test');
import * as helpers from './helpers';


test('Can see crawler backend module', async ({page}) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModule(page);
    await expect(page).toHaveURL('/typo3/module/page/crawler');
});

test('Can select and see crawler module start crawling', async ({page}) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleStartCrawling(page);
    await expect(page).toHaveURL('/typo3/module/page/crawler/start?id=1');
});

test('Can select and see crawler module log', async ({page}) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleCrawlerLog(page);
    await expect(page).toHaveURL('/typo3/module/page/crawler/log?id=1');
})

test('Can select and see crawler module process', async ({page}) => {
    await helpers.loginBackend(page)
    await helpers.openCrawlerModuleCrawlerProcesses(page)
    await expect(page).toHaveURL('/typo3/module/page/crawler/process?id=1');

    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('.module-body')).not.toContainText('Undefined array key "processMaxRunTime" in /var/www/html/vendor/tomasnorre/crawler/Classes/Domain/Repository/ProcessRepository.php line 193');
    await expect(page.locator('iframe[name="list_frame"]').contentFrame().locator('.module-body')).not.toContainText('Undefined array key "processMaxRunTime" in /var/www/html/vendor/tomasnorre/crawler/Classes/Domain/Repository/ProcessRepository.php line 163');
})
