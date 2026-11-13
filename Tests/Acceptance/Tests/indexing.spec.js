import {test, expect} from '@playwright/test';
import * as helpers from './helpers';

// "Subpage Three", which is crawled by the "default" configuration with the indexed_search reindex instruction
const pageId = 10;
const pageTitle = 'Subpage Three';

test.describe('Indexing', () => {
    test.describe.configure({timeout: 180_000});

    test.beforeEach(async ({page}) => {
        // Process entries left by other tests, otherwise they prevent adding the page to the queue again.
        helpers.processCrawlerQueue();

        // Make sure the page is not indexed before it is crawled.
        helpers.ddevQuery(`DELETE FROM index_phash WHERE data_page_id = ${pageId};`);
        await expect(await searchResult(page)).toHaveCount(0);
    });

    test('Pages added in the backend module are indexed', {tag: ['@v14']}, async ({page}) => {
        const lastQueueId = getLastQueueId();

        await helpers.loginBackend(page);
        await helpers.addQueueEntries(page, 'default', '99');
        helpers.processCrawlerQueue();

        expect(isCrawledSince(lastQueueId), 'Page is crawled by the worker').toBe(true);
        await expect(await searchResult(page)).toHaveCount(1);
    });

    test('Pages added with the buildQueue command are indexed', {tag: ['@v14']}, async ({page}) => {
        const lastQueueId = getLastQueueId();

        helpers.ddevExec(`bin/typo3 crawler:buildQueue ${pageId} default --mode=queue`);
        helpers.processCrawlerQueue();

        expect(isCrawledSince(lastQueueId), 'Page is crawled by the worker').toBe(true);
        await expect(await searchResult(page)).toHaveCount(1);
    });

    test('Pages crawled again from the log module are indexed again', {tag: ['@v14']}, async ({page}) => {
        // Crawl the page once, then remove it from the index again.
        helpers.ddevExec(`bin/typo3 crawler:buildQueue ${pageId} default --mode=exec`);
        helpers.ddevQuery(`DELETE FROM index_phash WHERE data_page_id = ${pageId};`);
        const queueId = Number(helpers.ddevQuery(
            `SELECT MAX(qid) FROM tx_crawler_queue WHERE page_id = ${pageId} AND exec_time > 0 ` +
            "AND parameters LIKE '%tx_indexedsearch_reindex%';"
        ));
        helpers.ddevQuery(`UPDATE tx_crawler_queue SET exec_time = 1 WHERE qid = ${queueId};`);

        await helpers.loginBackend(page);
        await page.goto(`/typo3/module/page/crawler/log?id=${pageId}`);
        const logModule = page.locator('iframe[name="list_frame"]').contentFrame();
        await Promise.all([
            page.waitForResponse((response) => response.url().includes(`qid_read=${queueId}&`)),
            logModule.locator(`a.refreshLink[href*="qid_read=${queueId}&"]`).click(),
        ]);
        helpers.processCrawlerQueue();

        const execTime = Number(helpers.ddevQuery(`SELECT exec_time FROM tx_crawler_queue WHERE qid = ${queueId};`));
        expect(execTime, 'Queue entry is crawled again').toBeGreaterThan(1);
        await expect(await searchResult(page)).toHaveCount(1);
    });
});

function getLastQueueId() {
    return Number(helpers.ddevQuery('SELECT COALESCE(MAX(qid), 0) FROM tx_crawler_queue;'));
}

function isCrawledSince(lastQueueId) {
    return helpers.ddevQuery(
        'SELECT COUNT(*) FROM tx_crawler_queue ' +
        `WHERE qid > ${lastQueueId} AND page_id = ${pageId} AND exec_time > 0 ` +
        "AND parameters LIKE '%tx_indexedsearch_reindex%';"
    ) !== '0';
}

async function searchResult(page) {
    await page.goto('/search');
    await page.fill('#tx-indexedsearch-searchbox-sword', 'Subpage');
    await page.click('#tx-indexedsearch-searchbox-button-submit');
    return page.locator('.tx-indexedsearch-title', {hasText: pageTitle});
}
