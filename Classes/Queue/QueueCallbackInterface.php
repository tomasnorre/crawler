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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A callback the crawler calls when it processes a queue entry added with QueueEntryWriter::addCallback(),
 * instead of crawling a URL.
 *
 * Implementations are services: with "autoconfigure: true" in the Services.yaml of their extension,
 * they get their dependencies by constructor injection.
 */
#[AutoconfigureTag(name: self::TAG)]
interface QueueCallbackInterface
{
    public const TAG = 'crawler.queue_callback';

    /**
     * Called when the crawler processes the queue entry.
     *
     * @param array $parameters The parameters given to QueueEntryWriter::addCallback()
     * @return mixed The result, stored JSON encoded as the result of the queue entry and shown in the crawler log
     */
    public function execute(array $parameters): mixed;
}
