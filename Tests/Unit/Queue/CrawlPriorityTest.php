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

use AOE\Crawler\Queue\CrawlPriority;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(CrawlPriority::class)]
class CrawlPriorityTest extends UnitTestCase
{
    #[DataProvider('sitemapPriorityDataProvider')]
    #[Test]
    public function fromSitemapPriority(float $sitemapPriority, CrawlPriority $expected): void
    {
        self::assertSame($expected, CrawlPriority::fromSitemapPriority($sitemapPriority));
    }

    public static function sitemapPriorityDataProvider(): iterable
    {
        yield '1.0 is high' => [1.0, CrawlPriority::High];
        yield '0.6 is high' => [0.6, CrawlPriority::High];
        yield '0.5 (default) is normal' => [0.5, CrawlPriority::Normal];
        yield '0.4 is low' => [0.4, CrawlPriority::Low];
        yield '0.0 is low' => [0.0, CrawlPriority::Low];
    }

    #[Test]
    public function getTransportName(): void
    {
        self::assertSame('crawler_high', CrawlPriority::High->getTransportName());
        self::assertSame('crawler', CrawlPriority::Normal->getTransportName());
        self::assertSame('crawler_low', CrawlPriority::Low->getTransportName());
    }
}
