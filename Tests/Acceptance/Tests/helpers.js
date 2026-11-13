import {expect} from "@playwright/test";
import {execSync} from "child_process";
import path from "path";

export async function loginBackend(page) {
    await page.goto('/typo3/');
    await page.fill('input#t3-username', 'admin');
    await page.fill('input#t3-password', 'password');
    await page.click('button#t3-login-submit')
}

export async function openCrawlerModule(page) {
    await page.getByText('Crawler module').click();
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


const devboxPath = path.resolve(__dirname, '../../../.devbox');

/**
 * Runs a shell command in the web container of the devbox.
 */
export function ddevExec(command) {
    return execSync(`ddev exec ${command}`, {cwd: devboxPath, encoding: 'utf8'});
}

/**
 * Runs a SQL query against the devbox database and returns the raw result, without column names.
 */
export function ddevQuery(sql) {
    return execSync('ddev mysql -N -B', {cwd: devboxPath, encoding: 'utf8', input: sql}).trim();
}

/**
 * Runs the message bus worker until all crawl messages are handled.
 */
export function processCrawlerQueue(maxRuns = 12) {
    for (let run = 0; run < maxRuns; run++) {
        const pending = Number(ddevQuery(
            "SELECT COUNT(*) FROM sys_messenger_messages WHERE queue_name IN ('crawler_high', 'crawler', 'crawler_low');"
        ));
        if (pending === 0) {
            return;
        }
        ddevExec('bin/typo3 messenger:consume crawler_high crawler crawler_low --time-limit=10');
    }
    throw new Error('The crawler queue was not processed completely.');
}
