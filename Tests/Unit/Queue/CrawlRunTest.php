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
    public function tracksHandledUrls(): void
    {
        $subject = new CrawlRun(0, 30);

        self::assertFalse($subject->isUrlHandled('https://example.com/||'));
        self::assertSame(0, $subject->countHandledUrls());

        $subject->markUrlHandled('https://example.com/||');
        $subject->markUrlHandled('https://example.com/||');
        $subject->markUrlHandled('https://example.com/about||');

        self::assertTrue($subject->isUrlHandled('https://example.com/||'));
        self::assertFalse($subject->isUrlHandled('https://example.com/contact||'));
        self::assertSame(2, $subject->countHandledUrls());
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
