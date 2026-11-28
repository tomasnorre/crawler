<?php

declare(strict_types=1);

namespace AOE\Crawler;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\CrawlStrategy\Interface\CrawlStrategyInterface;
use AOE\Crawler\Event\AfterUrlCrawledEvent;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Value\CrawlResult;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Fetches a URL based on the selected strategy or via a callback.
 * @internal since v9.2.5
 */
class QueueExecutor implements SingletonInterface
{
    public const INVALID_PARAMETERS_MESSAGE = 'The parameters of the queue entry could not be read';

    protected CrawlStrategyInterface $crawlStrategy;

    public function __construct(
        CrawlStrategyFactory $crawlStrategyFactory,
        private readonly EventDispatcher $eventDispatcher,
        private readonly CallbackExecutionStrategy $callbackExecutionStrategy,
    ) {
        $this->crawlStrategy = $crawlStrategyFactory->create();
    }

    /**
     * Takes a queue record and fetches the contents of the URL.
     * In the future, updating the queue item & additional signal/slot/events should also happen in here.
     *
     * @param array<string, mixed> $queueItem
     */
    public function executeQueueItem(array $queueItem): CrawlResult
    {
        $parameters = '';
        if (isset($queueItem['parameters'])) {
            $parameters = json_decode($queueItem['parameters'], true);
        }

        if (!is_array($parameters) || $parameters === []) {
            return CrawlResult::error(self::INVALID_PARAMETERS_MESSAGE);
        }
        if (isset($parameters[QueueEntryWriter::CALLBACK_PARAMETER])) {
            $className = $parameters[QueueEntryWriter::CALLBACK_PARAMETER];
            unset($parameters[QueueEntryWriter::CALLBACK_PARAMETER]);
            $result = CrawlResult::fromContent(
                $this->callbackExecutionStrategy->fetchByCallback((string) $className, $parameters)
            );
        } else {
            // Regular FE request
            $crawlerId = $this->generateCrawlerIdFromQueueItem($queueItem);

            $url = new Uri($parameters['url']);
            $result = $this->crawlStrategy->fetchUrlContents($url, $crawlerId);
            $this->eventDispatcher->dispatch(new AfterUrlCrawledEvent($parameters['url'], $result));
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $queueItem
     */
    private function generateCrawlerIdFromQueueItem(array $queueItem): string
    {
        return $queueItem['qid'] . ':' . md5(
            $queueItem['qid'] . '|' . $queueItem['set_id'] . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
        );
    }
}
