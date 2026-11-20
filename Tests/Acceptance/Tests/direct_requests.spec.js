import {test, expect} from '@playwright/test';
import * as helpers from './helpers';

// "Subpage Three", which is crawled by the "default" configuration
const pageId = 10;

// The devbox crawls with direct requests (makeDirectRequests), which render the pages within the worker.
test.describe('Direct requests', () => {
    test.describe.configure({timeout: 180_000});

    test('Crawled pages report the page and the user groups of the crawl', {tag: ['@v14']}, async () => {
        helpers.processCrawlerQueue();
        const lastQueueId = Number(helpers.query('SELECT COALESCE(MAX(qid), 0) FROM tx_crawler_queue;'));

        helpers.typo3(`crawler:buildQueue ${pageId} default --mode=queue`);
        helpers.processCrawlerQueue();

        const resultData = helpers.query(
            'SELECT result_data FROM tx_crawler_queue ' +
            `WHERE qid > ${lastQueueId} AND page_id = ${pageId} AND exec_time > 0 ORDER BY qid LIMIT 1;`
        );
        expect(resultData, 'Page is crawled by the worker').not.toBe('');
        const result = JSON.parse(JSON.parse(resultData).content);

        expect(result.errorlog ?? []).toEqual([]);
        expect(result.running).toBe(true);
        expect(result.vars.id).toBe(pageId);
        expect(result.vars.gr_list).toMatch(/^0,-[12]/);
    });
});
