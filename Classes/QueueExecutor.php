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
use AOE\Crawler\Value\CrawlResult;
use AOE\Crawler\Value\QueueEntry;
use AOE\Crawler\Value\QueueParameters;
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
     * Crawls the URL of a queue entry or calls its queue callback
     */
    public function executeQueueItem(QueueEntry $queueEntry): CrawlResult
    {
        $parameters = $queueEntry->getQueueParameters();
        if (!$parameters instanceof QueueParameters) {
            return CrawlResult::error(self::INVALID_PARAMETERS_MESSAGE);
        }
        if ($parameters->isCallback()) {
            return CrawlResult::fromContent($this->callbackExecutionStrategy->fetchByCallback(
                $parameters->getCallbackClassName(),
                $parameters->getCallbackParameters()
            ));
        }

        // Regular FE request
        $crawlerId = $queueEntry->getCrawlerId((string) $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
        $result = $this->crawlStrategy->fetchUrlContents(new Uri($parameters->getUrl()), $crawlerId);
        $this->eventDispatcher->dispatch(new AfterUrlCrawledEvent($parameters->getUrl(), $result));
        return $result;
    }
}
