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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\CrawlStrategy\CrawlStrategyInterface;
use AOE\Crawler\Event\AfterUrlCrawledEvent;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fetches a URL based on the selected strategy or via a callback.
 * @internal since v9.2.5
 */
class QueueExecutor implements SingletonInterface
{
    /**
     * @var CrawlStrategyInterface
     */
    protected $crawlStrategy;

    private EventDispatcher $eventDispatcher;

    public function __construct(CrawlStrategyFactory $crawlStrategyFactory, EventDispatcher $eventDispatcher)
    {
        $this->crawlStrategy = $crawlStrategyFactory->create();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Takes a queue record and fetches the contents of the URL.
     * In the future, updating the queue item & additional signal/slot/events should also happen in here.
     *
     * @return array|bool|mixed|string
     */
    public function executeQueueItem(array $queueItem, CrawlerController $crawlerController)
    {
        $parameters = '';
        if (isset($queueItem['parameters'])) {
            // Decode parameters:
            /** @var JsonCompatibilityConverter $jsonCompatibleConverter */
            $jsonCompatibleConverter = GeneralUtility::makeInstance(JsonCompatibilityConverter::class);
            $parameters = $jsonCompatibleConverter->convert($queueItem['parameters']);
        }

        if (! is_array($parameters) || empty($parameters)) {
            return 'ERROR';
        }
        if (isset($parameters['_CALLBACKOBJ'])) {
            $className = $parameters['_CALLBACKOBJ'];
            unset($parameters['_CALLBACKOBJ']);
            $result = GeneralUtility::makeInstance(CallbackExecutionStrategy::class)
                ->fetchByCallback($className, $parameters, $crawlerController);
            $result = ['content' => json_encode($result)];
        } else {
            // Regular FE request
            $crawlerId = $this->generateCrawlerIdFromQueueItem($queueItem);

            $url = new Uri($parameters['url']);
            $result = $this->crawlStrategy->fetchUrlContents($url, $crawlerId);
            if ($result !== false) {
                $result = ['content' => json_encode($result)];
                $this->eventDispatcher->dispatch(new AfterUrlCrawledEvent($parameters['url'], $result));
            }
        }
        return $result;
    }

    protected function generateCrawlerIdFromQueueItem(array $queueItem): string
    {
        return $queueItem['qid'] . ':' . md5($queueItem['qid'] . '|' . $queueItem['set_id'] . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }
}
