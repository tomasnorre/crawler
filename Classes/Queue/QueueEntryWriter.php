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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\AfterUrlAddedToQueueEvent;
use AOE\Crawler\Queue\Interface\QueueCallbackInterface;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the URLs of crawler configurations, writes them to the queue (tx_crawler_queue)
 * and hands them over to the message bus.
 *
 * @internal
 */
final class QueueEntryWriter
{
    /**
     * The parameter of a queue entry holding the class name of its QueueCallbackInterface
     */
    public const CALLBACK_PARAMETER = '_CALLBACKOBJ';

    private readonly array $extensionSettings;

    public function __construct(
        private readonly QueueRepository $queueRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlService $urlService,
        private readonly ProcessInstructionService $processInstructionService,
        private readonly CrawlUrlMessageDispatcher $crawlUrlMessageDispatcher,
        ExtensionConfigurationProvider $extensionConfigurationProvider,
        private readonly ClockInterface $clock,
    ) {
        $this->extensionSettings = $extensionConfigurationProvider->getExtensionConfiguration();
    }

    /**
     * Creates the URLs of a configuration and, depending on the options of the run, adds them to the queue
     * or collects them for download.
     *
     * @param array $configuration A configuration of the page, see PageConfigurationResolver
     * @return string List of URLs (meant for display in backend module)
     */
    public function compileUrlList(array $configuration, array $pageRow, CrawlRun $run): string
    {
        if (!is_array($configuration['URLs'])) {
            return 'ERROR - no URL generated';
        }
        if (!$this->processInstructionService->isAllowed(
            $configuration['subCfg']['procInstrFilter'] ?? '',
            $run->processingInstructions
        )) {
            return '';
        }
        $urlLog = [];
        $pageId = (int) $pageRow['uid'];
        $configurationHash = $this->getConfigurationHash($configuration);
        $skipInnerCheck = $this->queueRepository->noUnprocessedQueueEntriesForPageWithConfigurationHashExist(
            $pageId,
            $configurationHash
        );
        $userGroups = $configuration['subCfg']['userGroups'] ?? '';
        $processingInstructionFilter = $configuration['subCfg']['procInstrFilter'] ?? '';

        foreach ($configuration['URLs'] as $urlQuery) {
            $url = $this->urlService->getUrlFromPageAndQueryParameters(
                $pageId,
                $urlQuery,
                $configuration['subCfg']['baseUrl'] ?? null,
                (int) ($configuration['subCfg']['force_ssl'] ?? 0)
            );

            if (!$url instanceof UriInterface) {
                continue;
            }

            $url = (string) $url;

            if ($run->isUrlHandled($url, $userGroups, $processingInstructionFilter)) {
                // if the url key is registered just display it and do not resubmit is
                $urlLog[] = '<em><span class="text-muted">' . htmlspecialchars($url) . '</span></em>';
            } else {
                $urlLog[] = $this->handleUrl(
                    $run,
                    $pageId,
                    $url,
                    $configuration['subCfg'],
                    $configurationHash,
                    $skipInnerCheck
                );
            }
            $run->markUrlHandled($url, $userGroups, $processingInstructionFilter);
        }

        // Todo: Find a better option to have this correct in both backend (<br>) and cli (<new line>)
        return implode('<br>', $urlLog);
    }

    /**
     * Submits the URL for crawling or collects it for download, depending on the options of the run
     *
     * @return string The log line of the URL
     */
    private function handleUrl(
        CrawlRun $run,
        int $pageId,
        string $url,
        array $subConfiguration,
        string $configurationHash,
        bool $skipInnerCheck
    ): string {
        $formattedDate = BackendUtility::datetime($run->getScheduledTimeOfNextUrl());
        $run->addUrlListEntry('[' . $formattedDate . '] ' . $url);
        $urlLog = '[' . $formattedDate . '] ' . htmlspecialchars($url);

        if ($run->submitUrls) {
            // Submit for crawling!
            $added = $this->addUrl(
                $run,
                $pageId,
                $url,
                $subConfiguration,
                $run->scheduledTime,
                $configurationHash,
                $skipInnerCheck
            );
            // Collected queue entries are never stored, so addUrl() returns false for them as well
            if ($added === false && !$run->collectQueueEntriesOnly) {
                $urlLog .= ' (URL already existed)';
            }
        } elseif ($run->collectDownloadUrls) {
            $run->addDownloadUrl($url);
        }
        return $urlLog;
    }

    /**
     * Adds a URL to the queue, unless an equal entry is already queued. Returns whether it was added.
     * If the run only collects queue entries, the entry is added to the run instead and false is returned.
     *
     * @param array $subCfg Sub configuration array (from TS config)
     * @param int $scheduled Scheduled time
     * @param bool $skipInnerDuplicationCheck Skip the check for an equal entry
     */
    public function addUrl(
        CrawlRun $run,
        int $pageId,
        string $url,
        array $subCfg,
        int $scheduled,
        string $configurationHash = '',
        bool $skipInnerDuplicationCheck = false
    ): bool {
        $urlAdded = false;
        $rows = [];

        // Creating parameters:
        $parameters = [
            'url' => $url,
        ];

        // fe user group simulation:
        $uGs = implode(',', array_unique(GeneralUtility::intExplode(',', $subCfg['userGroups'] ?? '', true)));
        if ($uGs) {
            $parameters['feUserGroupList'] = $uGs;
        }

        // Setting processing instructions
        $parameters['procInstructions'] = GeneralUtility::trimExplode(',', $subCfg['procInstrFilter'] ?? '');
        $procInstrParams = $subCfg['procInstrParams.'] ?? null;
        if (is_array($procInstrParams)) {
            $parameters['procInstrParams'] = $procInstrParams;
        }

        // Compile value array:
        $parameters_serialized = json_encode($parameters) ?: '';
        $fieldArray = [
            'page_id' => $pageId,
            'parameters' => $parameters_serialized,
            'parameters_hash' => md5($parameters_serialized),
            'configuration_hash' => $configurationHash,
            'scheduled' => $scheduled,
            'exec_time' => 0,
            'set_id' => $run->setId,
            'result_data' => '',
            'configuration' => $subCfg['key'],
        ];

        if ($run->collectQueueEntriesOnly) {
            // the entries will only be registered and not stored to the database
            $run->addQueueEntry($fieldArray);
        } else {
            if (!$skipInnerDuplicationCheck) {
                // check if there is already an equal entry
                $rows = $this->queueRepository->getDuplicateQueueItemsIfExists(
                    (bool) $this->extensionSettings['enableTimeslot'],
                    $scheduled,
                    $this->clock->now()->getTimestamp(),
                    $fieldArray['page_id'],
                    $fieldArray['parameters_hash']
                );
            }
            if ($rows === []) {
                $uid = $this->queueRepository->add($fieldArray);
                $urlAdded = true;

                $this->eventDispatcher->dispatch(new AfterUrlAddedToQueueEvent((string) $uid, $fieldArray));
                $this->dispatchCrawlUrlMessage($uid, $pageId, $scheduled);
            }
        }

        return $urlAdded;
    }

    /**
     * Adds a queue entry that calls a QueueCallbackInterface instead of crawling a URL. Returns the queue id.
     *
     * @param class-string<QueueCallbackInterface> $callbackClassName
     * @param array $parameters Handed over to QueueCallbackInterface::execute(), must be JSON encodable
     * @param int $pageId The page the queue entry is shown with in the crawler log
     * @param int $scheduled When to execute the callback, 0 for now
     * @param int $setId The set the queue entry belongs to, fx. to filter the crawler log
     * @psalm-api Used by other extensions, see Documentation/Features/QueueCallbacks
     */
    public function addCallback(
        string $callbackClassName,
        array $parameters = [],
        int $pageId = 0,
        int $scheduled = 0,
        int $setId = 0
    ): int {
        $scheduled = $scheduled ?: $this->clock->now()->getTimestamp();
        $parameters[self::CALLBACK_PARAMETER] = $callbackClassName;

        $queueId = $this->queueRepository->add([
            'page_id' => $pageId,
            'parameters' => json_encode($parameters, JSON_THROW_ON_ERROR),
            'scheduled' => $scheduled,
            'exec_time' => 0,
            'set_id' => $setId,
            'result_data' => '',
        ]);
        $this->dispatchCrawlUrlMessage($queueId, $pageId, $scheduled);

        return $queueId;
    }

    /**
     * Returns a md5 hash generated from a serialized configuration array.
     */
    private function getConfigurationHash(array $configuration): string
    {
        unset($configuration['paramExpanded']);
        unset($configuration['URLs']);
        return md5(serialize($configuration));
    }

    /**
     * Hands the queue record over to the message bus. Records scheduled in the future are delayed accordingly.
     */
    private function dispatchCrawlUrlMessage(int $queueId, int $pageId, int $scheduled): void
    {
        $this->crawlUrlMessageDispatcher->dispatch(
            new CrawlUrlMessage($queueId),
            $pageId,
            $scheduled - $this->clock->now()->getTimestamp()
        );
    }
}
