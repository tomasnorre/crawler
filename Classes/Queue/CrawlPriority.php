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

/**
 * Priority of a crawl message. Every priority has its own transport, a worker consuming
 * "crawler_high crawler crawler_low" always processes the higher priorities first.
 */
enum CrawlPriority: int
{
    case High = 1;
    case Normal = 2;
    case Low = 3;

    /**
     * The sitemap priority of EXT:seo ranges from 0.0 to 1.0 with 0.5 as default.
     */
    public static function fromSitemapPriority(float $sitemapPriority): self
    {
        return match (true) {
            $sitemapPriority > 0.5 => self::High,
            $sitemapPriority < 0.5 => self::Low,
            default => self::Normal,
        };
    }

    public function getTransportName(): string
    {
        return match ($this) {
            self::High => 'crawler_high',
            self::Normal => 'crawler',
            self::Low => 'crawler_low',
        };
    }
}
