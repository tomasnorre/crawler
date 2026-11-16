<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * One run of compiling the URLs of crawler configurations, fx. one call of crawler:buildQueue:
 * its options, and the URLs and queue entries it collects.
 *
 * @internal
 */
final class CrawlRun
{
    /**
     * The set id of the queue entries written in this run
     */
    public readonly int $setId;

    /**
     * An id per URL, so URLs already handled in this run are not added again
     *
     * @var array<string, true>
     */
    private array $handledUrlKeys = [];

    /**
     * @var array<string, string>
     */
    private array $downloadUrls = [];

    /**
     * @var list<string>
     */
    private array $urlList = [];

    /**
     * @var list<array<string, mixed>>
     */
    private array $queueEntries = [];

    /**
     * @param int $scheduledTime Unix time to schedule the crawling to
     * @param int $requestsPerMinute Number of requests per minute (creates the interleave between requests)
     * @param bool $submitUrls If set, the URLs are added to the queue
     * @param bool $collectDownloadUrls If set (and $submitUrls is not), the URLs are collected for download
     * @param bool $collectQueueEntriesOnly If set, queue entries are only collected and not stored in the database
     * @param list<string> $processingInstructions If not empty, only URLs of these processing instructions are used
     * @param array $configurationSelection If not empty, only these configuration keys are used
     * @param int|null $setId The set id of the queue entries, a new one if null
     */
    public function __construct(
        public readonly int $scheduledTime,
        public readonly int $requestsPerMinute,
        public readonly bool $submitUrls = false,
        public readonly bool $collectDownloadUrls = false,
        public readonly bool $collectQueueEntriesOnly = false,
        public readonly array $processingInstructions = [],
        public readonly array $configurationSelection = [],
        ?int $setId = null,
    ) {
        $this->setId = $setId ?? GeneralUtility::md5int(microtime());
    }

    public function isUrlHandled(string $urlKey): bool
    {
        return isset($this->handledUrlKeys[$urlKey]);
    }

    public function markUrlHandled(string $urlKey): void
    {
        $this->handledUrlKeys[$urlKey] = true;
    }

    /**
     * The number of different URLs handled in this run
     */
    public function countHandledUrls(): int
    {
        return count($this->handledUrlKeys);
    }

    public function addDownloadUrl(string $url): void
    {
        $this->downloadUrls[$url] = $url;
    }

    /**
     * @return array<string, string>
     */
    public function getDownloadUrls(): array
    {
        return $this->downloadUrls;
    }

    /**
     * @param string $entry A URL with its scheduled time, fx. "[14-11-26 12:00] https://example.com/"
     */
    public function addUrlListEntry(string $entry): void
    {
        $this->urlList[] = $entry;
    }

    /**
     * @return list<string>
     */
    public function getUrlList(): array
    {
        return $this->urlList;
    }

    /**
     * @param array<string, mixed> $queueEntry
     */
    public function addQueueEntry(array $queueEntry): void
    {
        $this->queueEntries[] = $queueEntry;
    }

    /**
     * The queue entries collected when $collectQueueEntriesOnly is set
     *
     * @return list<array<string, mixed>>
     */
    public function getQueueEntries(): array
    {
        return $this->queueEntries;
    }
}
