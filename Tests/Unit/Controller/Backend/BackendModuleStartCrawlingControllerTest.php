<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Controller\Backend;

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

use AOE\Crawler\Controller\Backend\BackendModuleStartCrawlingController;
use AOE\Crawler\Tests\Unit\FrozenClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(BackendModuleStartCrawlingController::class)]
final class BackendModuleStartCrawlingControllerTest extends UnitTestCase
{
    /**
     * The "Scheduled" option of the module: now, at midnight or at 04:00 of today.
     */
    #[DataProvider('scheduledTimeDataProvider')]
    #[Test]
    public function getScheduledTimeUsesTheClock(string $option, string $expected): void
    {
        $subject = $this->createSubjectWithClock(new FrozenClock('2026-11-16 15:30:42'));

        self::assertSame(
            (new \DateTimeImmutable($expected))->getTimestamp(),
            (new \ReflectionMethod($subject, 'getScheduledTime'))->invoke($subject, $option)
        );
    }

    public static function scheduledTimeDataProvider(): iterable
    {
        yield 'now' => ['now', '2026-11-16 15:30:42'];
        yield 'midnight' => ['midnight', '2026-11-16 00:00:00'];
        yield '04:00' => ['04:00', '2026-11-16 04:00:00'];
        yield 'unknown option is now' => ['tomorrow', '2026-11-16 15:30:42'];
    }

    #[Test]
    public function createDownloadResponseReturnsTheUrlsAsTextFileDownload(): void
    {
        $subject = $this->createSubjectWithClock(new FrozenClock('2026-11-16 15:30:42'));

        $response = (new \ReflectionMethod($subject, 'createDownloadResponse'))->invoke($subject, [
            'https://example.com/' => 'https://example.com/',
            'https://example.com/page' => 'https://example.com/page',
        ]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/octet-stream', $response->getHeaderLine('Content-Type'));
        self::assertSame('attachment; filename=CrawlerUrls.txt', $response->getHeaderLine('Content-Disposition'));
        self::assertSame("https://example.com/\r\nhttps://example.com/page", (string) $response->getBody());
    }

    private function createSubjectWithClock(ClockInterface $clock): BackendModuleStartCrawlingController
    {
        $subject = (new \ReflectionClass(BackendModuleStartCrawlingController::class))->newInstanceWithoutConstructor();
        \Closure::bind(
            static function (BackendModuleStartCrawlingController $subject) use ($clock): void {
                $subject->clock = $clock;
                $subject->responseFactory = new ResponseFactory();
                $subject->streamFactory = new StreamFactory();
            },
            null,
            BackendModuleStartCrawlingController::class
        )($subject);

        return $subject;
    }
}
