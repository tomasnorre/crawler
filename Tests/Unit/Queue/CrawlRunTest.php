<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Queue;

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

use AOE\Crawler\Queue\CrawlRun;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(CrawlRun::class)]
final class CrawlRunTest extends UnitTestCase
{
    #[Test]
    public function keepsGivenSetId(): void
    {
        self::assertSame(42, (new CrawlRun(0, 30, setId: 42))->setId);
    }

    #[Test]
    public function createsSetIdIfNoneIsGiven(): void
    {
        self::assertGreaterThan(0, (new CrawlRun(0, 30))->setId);
    }

    #[Test]
    public function onlyPreviewsUrlsByDefault(): void
    {
        $subject = new CrawlRun(0, 30);

        self::assertFalse($subject->submitUrls);
        self::assertFalse($subject->collectDownloadUrls);
        self::assertFalse($subject->collectQueueEntriesOnly);
        self::assertSame([], $subject->processingInstructions);
        self::assertSame([], $subject->configurationSelection);
    }

    #[Test]
    public function tracksHandledUrls(): void
    {
        $subject = new CrawlRun(0, 30);

        self::assertFalse($subject->isUrlHandled('https://example.com/'));
        self::assertSame(0, $subject->countHandledUrls());

        $subject->markUrlHandled('https://example.com/');
        $subject->markUrlHandled('https://example.com/');
        $subject->markUrlHandled('https://example.com/about');

        self::assertTrue($subject->isUrlHandled('https://example.com/'));
        self::assertFalse($subject->isUrlHandled('https://example.com/contact'));
        self::assertSame(2, $subject->countHandledUrls());
    }

    #[Test]
    public function tracksUrlsPerUserGroupsAndProcessingInstructionFilter(): void
    {
        $subject = new CrawlRun(0, 30);

        $subject->markUrlHandled('https://example.com/', '1,2', 'tx_indexedsearch_reindex');

        self::assertTrue($subject->isUrlHandled('https://example.com/', '1,2', 'tx_indexedsearch_reindex'));
        self::assertFalse($subject->isUrlHandled('https://example.com/'));
        self::assertFalse($subject->isUrlHandled('https://example.com/', '1,2'));
        self::assertFalse($subject->isUrlHandled('https://example.com/', '', 'tx_indexedsearch_reindex'));
        self::assertFalse($subject->isUrlHandled('https://example.com/', '1', '2tx_indexedsearch_reindex'));
    }

    #[DataProvider('scheduledTimeOfNextUrlDataProvider')]
    #[Test]
    public function getScheduledTimeOfNextUrlAddsTheIntervalOfEveryHandledUrl(
        int $scheduledTime,
        int $requestsPerMinute,
        int $handledUrls,
        int $expected
    ): void {
        $subject = new CrawlRun($scheduledTime, $requestsPerMinute);
        for ($i = 0; $i < $handledUrls; $i++) {
            $subject->markUrlHandled('https://example.com/' . $i);
        }

        self::assertSame($expected, $subject->getScheduledTimeOfNextUrl());
    }

    public static function scheduledTimeOfNextUrlDataProvider(): iterable
    {
        // 1_000_020 is a full minute
        yield 'first URL at the full minute of the scheduled time' => [1_000_050, 1, 0, 1_000_020];
        yield 'one minute per URL' => [1_000_020, 1, 1, 1_000_080];
        yield 'one minute per URL from the end of a minute' => [1_000_079, 1, 1, 1_000_080];
        yield 'two URLs per minute' => [1_000_020, 2, 1, 1_000_020];
        yield 'two URLs per minute, second minute' => [1_000_020, 2, 2, 1_000_080];
        yield 'interval of 8.57 seconds is rounded up' => [1_000_071, 7, 1, 1_000_080];
        yield 'interval of 17.14 seconds is rounded down' => [1_000_062, 7, 2, 1_000_020];
    }

    #[Test]
    public function collectsDownloadUrlsOnce(): void
    {
        $subject = new CrawlRun(0, 30);

        $subject->addDownloadUrl('https://example.com/');
        $subject->addDownloadUrl('https://example.com/about');
        $subject->addDownloadUrl('https://example.com/');

        self::assertSame([
            'https://example.com/' => 'https://example.com/',
            'https://example.com/about' => 'https://example.com/about',
        ], $subject->getDownloadUrls());
    }

    #[Test]
    public function collectsUrlListAndQueueEntries(): void
    {
        $subject = new CrawlRun(0, 30);

        $subject->addUrlListEntry('[14-11-26 12:00] https://example.com/');
        $subject->addQueueEntry([
            'page_id' => 1,
        ]);

        self::assertSame(['[14-11-26 12:00] https://example.com/'], $subject->getUrlList());
        self::assertSame([[
            'page_id' => 1,
        ]], $subject->getQueueEntries());
    }
}
