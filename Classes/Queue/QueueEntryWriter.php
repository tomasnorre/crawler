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
use AOE\Crawler\Configuration\ExtensionSettings;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\AfterUrlAddedToQueueEvent;
use AOE\Crawler\Queue\Interface\QueueCallbackInterface;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use AOE\Crawler\Value\QueueEntry;
use AOE\Crawler\Value\QueueParameters;
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
    public const CALLBACK_PARAMETER = QueueParameters::CALLBACK_PARAMETER;

    private readonly ExtensionSettings $extensionSettings;

    public function __construct(
        private readonly QueueRepository $queueRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlService $urlService,
        private readonly ProcessInstructionService $processInstructionService,
        private readonly CrawlUrlMessageDispatcher $crawlUrlMessageDispatcher,
        ExtensionConfigurationProvider $extensionConfigurationProvider,
        private readonly ClockInterface $clock,
    ) {
        $this->extensionSettings = $extensionConfigurationProvider->getExtensionSettings();
    }

    /**
     * Creates the URLs of a configuration and, depending on the options of the run, adds them to the queue
     * or collects them for download.
     *
     * @param array<string, mixed> $configuration A configuration of the page, see PageConfigurationResolver
     * @param array<string, mixed> $pageRow
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
     * @param array<string, mixed> $subConfiguration
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
     * @param array<string, mixed> $subCfg Sub configuration array (from TS config)
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

        $parameters = $this->createUrlParameters($url, $subCfg)->toJson();
        $queueEntry = new QueueEntry(
            pageId: $pageId,
            parameters: $parameters,
            parametersHash: md5($parameters),
            configurationHash: $configurationHash,
            scheduled: $scheduled,
            setId: $run->setId,
            configuration: (string) $subCfg['key'],
        );
        $fieldArray = $queueEntry->toFieldArray();

        if ($run->collectQueueEntriesOnly) {
            // the entries will only be registered and not stored to the database
            $run->addQueueEntry($queueEntry);
        } else {
            if (!$skipInnerDuplicationCheck) {
                // check if there is already an equal entry
                $rows = $this->queueRepository->getDuplicateQueueItemsIfExists(
                    $this->extensionSettings->enableTimeslot,
                    $scheduled,
                    $this->clock->now()->getTimestamp(),
                    $queueEntry->pageId,
                    $queueEntry->parametersHash
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
     * @param array<string, mixed> $subCfg
     */
    private function createUrlParameters(string $url, array $subCfg): QueueParameters
    {
        // fe user group simulation, "0" are no user groups
        $userGroups = implode(',', array_unique(GeneralUtility::intExplode(',', $subCfg['userGroups'] ?? '', true)));
        $processingInstructionParameters = $subCfg['procInstrParams.'] ?? null;
        return QueueParameters::forUrl(
            $url,
            $userGroups === '0' ? '' : $userGroups,
            GeneralUtility::trimExplode(',', $subCfg['procInstrFilter'] ?? ''),
            is_array($processingInstructionParameters) ? $processingInstructionParameters : null,
        );
    }

    /**
     * Adds a queue entry that calls a QueueCallbackInterface instead of crawling a URL. Returns the queue id.
     *
     * @param class-string<QueueCallbackInterface> $callbackClassName
     * @param array<array-key, mixed> $parameters Handed over to QueueCallbackInterface::execute(), must be JSON encodable
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

        $queueId = $this->queueRepository->add((new QueueEntry(
            pageId: $pageId,
            parameters: QueueParameters::forCallback($callbackClassName, $parameters)->toJson(),
            scheduled: $scheduled,
            setId: $setId,
        ))->toFieldArray());
        $this->dispatchCrawlUrlMessage($queueId, $pageId, $scheduled);

        return $queueId;
    }

    /**
     * Returns a md5 hash generated from a serialized configuration array.
     *
     * @param array<string, mixed> $configuration
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
