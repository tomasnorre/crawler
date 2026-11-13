import {test, expect} from '@playwright/test';
import * as helpers from './helpers';

test('Crawled pages are indexed by indexed_search', {tag: ['@v14']}, async ({page}) => {
    test.setTimeout(180_000);

    // Make sure "Subpage Three" (page 10) is not indexed before it is crawled.
    helpers.ddevQuery('DELETE FROM index_phash WHERE data_page_id = 10;');

    await page.goto('/search');
    await page.fill('#tx-indexedsearch-searchbox-sword', 'Subpage');
    await page.click('#tx-indexedsearch-searchbox-button-submit');
    await expect(page.locator('.tx-indexedsearch-title', {hasText: 'Subpage Three'})).toHaveCount(0);

    // Process entries left by other tests, otherwise they prevent adding page 10 to the queue again.
    helpers.processCrawlerQueue();
    // Only queue entries created by this test count.
    const lastQueueId = Number(helpers.ddevQuery('SELECT COALESCE(MAX(qid), 0) FROM tx_crawler_queue;'));

    // The "default" configuration crawls with the indexed_search reindex processing instruction.
    await helpers.loginBackend(page);
    await helpers.addQueueEntries(page, 'default', '99');
    helpers.processCrawlerQueue();

    const crawled = helpers.ddevQuery(
        "SELECT exec_time > 0 FROM tx_crawler_queue " +
        `WHERE qid > ${lastQueueId} AND page_id = 10 AND parameters LIKE '%tx_indexedsearch_reindex%' ` +
        'ORDER BY qid DESC LIMIT 1;'
    );
    expect(crawled, 'Page 10 is crawled by the worker').toBe('1');

    await page.goto('/search');
    await page.fill('#tx-indexedsearch-searchbox-sword', 'Subpage');
    await page.click('#tx-indexedsearch-searchbox-button-submit');
    await expect(page.locator('.tx-indexedsearch-title', {hasText: 'Subpage Three'})).toHaveCount(1);
});
