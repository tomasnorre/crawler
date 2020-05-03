<?php

declare(strict_types=1);

namespace AOE\Crawler;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy;
use AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy;
use AOE\Crawler\Utility\SignalSlotUtility;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fetches a URL based on the selected strategy or via a callback.
 */
class QueueExecutor implements SingletonInterface
{
    /**
     * @var GuzzleExecutionStrategy|SubProcessExecutionStrategy
     */
    protected $selectedStrategy;

    /**
     * @var array
     */
    protected $extensionSettings;

    public function __construct(?ExtensionConfigurationProvider $configurationProvider = null)
    {
        $configurationProvider = $configurationProvider ?? GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
        $settings = $configurationProvider->getExtensionConfiguration();
        $this->extensionSettings = is_array($settings) ? $settings : [];
        if ($this->extensionSettings['makeDirectRequests']) {
            $this->selectedStrategy = GeneralUtility::makeInstance(SubProcessExecutionStrategy::class);
        } else {
            $this->selectedStrategy = GeneralUtility::makeInstance(GuzzleExecutionStrategy::class);
        }
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
        if ($parameters['_CALLBACKOBJ']) {
            $className = $parameters['_CALLBACKOBJ'];
            unset($parameters['_CALLBACKOBJ']);
            $result = GeneralUtility::makeInstance(CallbackExecutionStrategy::class)
                ->fetchByCallback($className, $parameters, $crawlerController);
            $result = ['content' => serialize($result)];
        } else {
            // Regular FE request
            $crawlerId = $this->generateCrawlerIdFromQueueItem($queueItem);

            // Get result:
            $url = new Uri($parameters['url']);
            $result = $this->selectedStrategy->fetchUrlContents($url, $crawlerId);
            if ($result !== false) {
                $result = ['content' => serialize($result)];
            }

            $signalPayload = ['url' => $parameters['url'], 'result' => $result];
            SignalSlotUtility::emitSignal(
                self::class,
                SignalSlotUtility::SIGNAL_URL_CRAWLED,
                $signalPayload
            );
        }
        return $result;
    }

    protected function generateCrawlerIdFromQueueItem(array $queueItem): string
    {
        return $queueItem['qid'] . ':' . md5($queueItem['qid'] . '|' . $queueItem['set_id'] . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }
}
