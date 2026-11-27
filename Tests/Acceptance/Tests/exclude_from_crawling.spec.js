import {test, expect} from '@playwright/test';
import * as helpers from './helpers';

// "Page with Subpages" (uid 7) of the devbox, with the subpages "Subpage One", "Subpage Two" and "Subpage Three"
const pageId = 7;

function setExclusion(exclude, excludeSubpages) {
    helpers.query(
        `UPDATE pages SET tx_crawler_exclude = ${exclude}, tx_crawler_exclude_subpages = ${excludeSubpages} WHERE uid = ${pageId};`
    );
}

function crawledUrls() {
    return helpers.typo3(`crawler:buildQueue ${pageId} default --depth=1 --mode=url`);
}

/**
 * The edit form is either shown in the content frame of the backend or as the page itself
 */
async function editForm(page) {
    const contentFrame = page.locator('iframe[name="list_frame"]');
    return (await contentFrame.count()) > 0 ? contentFrame.contentFrame() : page;
}

test.describe('Exclude from crawling', () => {
    test.afterEach(() => {
        setExclusion(0, 0);
    });

    test('Page properties offer to exclude the page and its subpages from crawling', {tag: '@v14'}, async ({page}) => {
        await helpers.loginBackend(page);
        await page.goto(`/typo3/record/edit?edit[pages][${pageId}]=edit`);
        const form = await editForm(page);
        await form.getByRole('tab', {name: 'Behavior'}).click();

        await form.getByLabel('Exclude from crawling', {exact: true}).check();
        await form.getByLabel('Exclude subpages from crawling', {exact: true}).check();
        await form.getByRole('button', {name: 'Save'}).click();
        await expect(page.locator('#nprogress')).toHaveCount(0);

        await expect.poll(() => helpers.query(
            `SELECT CONCAT(tx_crawler_exclude, '-', tx_crawler_exclude_subpages) FROM pages WHERE uid = ${pageId};`
        )).toBe('1-1');
    });

    test('Saving a page excluded from crawling does not add it to the queue', {tag: '@v14'}, async ({page}) => {
        helpers.query(`DELETE FROM tx_crawler_queue WHERE page_id = ${pageId};`);

        await helpers.loginBackend(page);
        await page.goto(`/typo3/record/edit?edit[pages][${pageId}]=edit`);
        const form = await editForm(page);
        await form.getByRole('tab', {name: 'Behavior'}).click();
        await form.getByLabel('Exclude from crawling', {exact: true}).check();
        await form.getByRole('button', {name: 'Save'}).click();
        await expect(page.locator('#nprogress')).toHaveCount(0);
        await expect.poll(() => helpers.query(
            `SELECT tx_crawler_exclude FROM pages WHERE uid = ${pageId};`
        )).toBe('1');

        expect(helpers.query(`SELECT COUNT(*) FROM tx_crawler_queue WHERE page_id = ${pageId};`)).toBe('0');
    });

    test('Pages queued before they are excluded from crawling are not crawled', {tag: '@v14'}, async () => {
        test.setTimeout(180_000);
        const branch = `${pageId}, 8, 9, 10`;
        // Process entries left by other tests, otherwise they prevent adding the pages to the queue again.
        helpers.processCrawlerQueue();
        helpers.query(`DELETE FROM tx_crawler_queue WHERE page_id IN (${branch});`);

        helpers.typo3(`crawler:buildQueue ${pageId} default --depth=1 --mode=queue`);
        setExclusion(1, 1);
        helpers.processCrawlerQueue();

        expect(Number(helpers.query(
            `SELECT COUNT(*) FROM tx_crawler_queue WHERE page_id IN (${branch}) AND exec_time > 0;`
        )), 'The queue entries of the branch are processed').toBe(4);
        expect(helpers.query(
            `SELECT COUNT(*) FROM tx_crawler_queue WHERE page_id IN (${branch}) ` +
            "AND result_data NOT LIKE '%excluded from crawling%';"
        ), 'No page of the branch is crawled').toBe('0');
    });

    test('Page excluded from crawling is skipped, its subpages are crawled', {tag: '@v14'}, async () => {
        setExclusion(1, 0);

        const urls = crawledUrls();

        expect(urls).not.toMatch(/\/page-with-subpages\s/);
        expect(urls).toContain('/page-with-subpages/subpage-one');
        expect(urls).toContain('/page-with-subpages/subpage-two');
        expect(urls).toContain('/page-with-subpages/subpage-three');
    });

    test('Page excluded from crawling with its subpages skips the whole branch', {tag: '@v14'}, async () => {
        setExclusion(1, 1);

        const urls = crawledUrls();

        expect(urls).not.toContain('/page-with-subpages');
    });

    test('Start Crawling shows why pages are skipped', {tag: '@v14'}, async ({page}) => {
        setExclusion(1, 1);

        await helpers.loginBackend(page);
        await page.goto(`/typo3/module/page/crawler/start?id=${pageId}`);
        await expect(page.locator('#nprogress')).toHaveCount(0);
        const module = page.locator('iframe[name="list_frame"]').contentFrame();
        await module.locator('select[name="configurationSelection[]"]').selectOption('default');
        await module.locator('select[name="crawlingDepth"]').selectOption('1');
        await module.getByRole('button', {name: 'Update'}).click();
        await expect(page.locator('#nprogress')).toHaveCount(0);

        await expect(module.getByRole('document')).toContainText('Because page is excluded from crawling');
        await expect(module.getByRole('document')).toContainText(
            `Because parent page "${pageId}" is excluded from crawling with its subpages`
        );
    });
});
