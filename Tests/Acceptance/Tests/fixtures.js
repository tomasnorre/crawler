import {test as base, expect, chromium} from '@playwright/test';

/*
 * The tests launch Chromium by default. To run them in an already running browser speaking the Chrome DevTools
 * Protocol instead, fx. Lightpanda (https://lightpanda.io/) started with
 *   lightpanda serve --host 127.0.0.1 --port 9222 --insecure-disable-tls-host-verification
 * set
 *   PLAYWRIGHT_CDP_ENDPOINT   its endpoint, fx. "ws://127.0.0.1:9222"
 */
const cdpEndpoint = process.env.PLAYWRIGHT_CDP_ENDPOINT;

export const test = cdpEndpoint
    ? base.extend({
        browser: [async ({}, use) => {
            const browser = await chromium.connectOverCDP(cdpEndpoint);
            await use(browser);
            await browser.close();
        }, {scope: 'worker'}],
    })
    : base;

export {expect};
