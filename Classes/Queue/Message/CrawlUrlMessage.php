<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue\Message;

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
 * Message requesting that the URL stored in a tx_crawler_queue record is crawled.
 *
 * The queue record holds the URL, parameters and (after crawling) the result,
 * the message only carries the reference to it.
 */
final readonly class CrawlUrlMessage
{
    /**
     * @param int $queueId uid (qid) of the tx_crawler_queue record
     * @param bool $force crawl the record even if it has already been crawled
     */
    public function __construct(
        public int $queueId,
        public bool $force = false,
    ) {
    }
}
