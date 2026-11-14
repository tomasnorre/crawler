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
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the URLs of crawler configurations, writes them to the queue (tx_crawler_queue)
 * and hands them over to the message bus.
 *
 * Not final, so tests can replace getCurrentTime().
 *
 * @internal
 */
class QueueEntryWriter
{
    /**
     * The set id of the queue entries written in the current run
     */
    public int $setId = 0;

    /**
     * If set, the queue entries are only collected in $queueEntries and not stored in the database
     */
    public bool $registerQueueEntriesInternallyOnly = false;

    /**
     * The queue entries collected when $registerQueueEntriesInternallyOnly is set
     */
    public array $queueEntries = [];

    /**
     * The URLs with their scheduled time, fx. "[14-11-26 12:00] https://example.com/"
     *
     * @var list<string>
     */
    public array $urlList = [];

    private readonly array $extensionSettings;

    public function __construct(
        private readonly QueueRepository $queueRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlService $urlService,
        private readonly ProcessInstructionService $processInstructionService,
        private readonly CrawlUrlMessageDispatcher $crawlUrlMessageDispatcher,
        ExtensionConfigurationProvider $extensionConfigurationProvider,
    ) {
        $this->extensionSettings = $extensionConfigurationProvider->getExtensionConfiguration();
    }

    /**
     * Creates the URLs of a configuration and, if $submitCrawlUrls is set, adds them to the queue.
     * See Web > Info module script + "indexed_search"'s crawler hook-client using this!
     *
     * @param array $configuration A configuration of the page, see PageConfigurationResolver
     * @param array $pageRow Page row
     * @param int $scheduledTime Unix time to schedule indexing to, typically time()
     * @param int $reqMinute Number of requests per minute (creates the interleave between requests)
     * @param bool $submitCrawlUrls If set, submits the URLs to queue
     * @param bool $downloadCrawlUrls If set (and $submitCrawlUrls is not) fills $downloadUrls with the URLs
     * @param array $duplicateTrack An id per URL, so URLs already handled in this run are not added again
     * @param array $downloadUrls Filled with the URLs, if $downloadCrawlUrls is set
     * @param array $incomingProcInstructions Processing instructions, if empty the URLs are not filtered by them
     * @return string List of URLs (meant for display in backend module)
     */
    public function compileUrlList(
        array $configuration,
        array $pageRow,
        int $scheduledTime,
        int $reqMinute,
        bool $submitCrawlUrls,
        bool $downloadCrawlUrls,
        array &$duplicateTrack,
        array &$downloadUrls,
        array $incomingProcInstructions
    ): string {
        if (!is_array($configuration['URLs'])) {
            return 'ERROR - no URL generated';
        }
        $urlLog = [];
        $pageId = (int) $pageRow['uid'];
        $configurationHash = $this->getConfigurationHash($configuration);
        $skipInnerCheck = $this->queueRepository->noUnprocessedQueueEntriesForPageWithConfigurationHashExist(
            $pageId,
            $configurationHash
        );

        foreach ($configuration['URLs'] as $urlQuery) {
            if (!$this->processInstructionService->isAllowed(
                $configuration['subCfg']['procInstrFilter'] ?? '',
                $incomingProcInstructions
            )) {
                continue;
            }
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

            // Create key by which to determine unique-ness:
            $uKey = $url . '|' . ($configuration['subCfg']['userGroups'] ?? '') . '|' . ($configuration['subCfg']['procInstrFilter'] ?? '');

            if (isset($duplicateTrack[$uKey])) {
                //if the url key is registered just display it and do not resubmit is
                $urlLog[] = '<em><span class="text-muted">' . htmlspecialchars($url) . '</span></em>';
            } else {
                // Scheduled time:
                $schTime = $scheduledTime + round(count($duplicateTrack) * (60 / $reqMinute));
                $schTime = intval($schTime / 60) * 60;
                $formattedDate = BackendUtility::datetime($schTime);
                $this->urlList[] = '[' . $formattedDate . '] ' . $url;
                $urlList = '[' . $formattedDate . '] ' . htmlspecialchars($url);

                // Submit for crawling!
                if ($submitCrawlUrls) {
                    $added = $this->addUrl(
                        $pageId,
                        $url,
                        $configuration['subCfg'],
                        $scheduledTime,
                        $configurationHash,
                        $skipInnerCheck
                    );
                    if ($added === false) {
                        $urlList .= ' (URL already existed)';
                    }
                } elseif ($downloadCrawlUrls) {
                    $downloadUrls[$url] = $url;
                }
                $urlLog[] = $urlList;
            }
            $duplicateTrack[$uKey] = true;
        }

        // Todo: Find a better option to have this correct in both backend (<br>) and cli (<new line>)
        return implode('<br>', $urlLog);
    }

    /**
     * Adds a URL to the queue, unless an equal entry is already queued. Returns whether it was added.
     *
     * @param array $subCfg Sub configuration array (from TS config)
     * @param int $scheduled Scheduled time
     * @param bool $skipInnerDuplicationCheck Skip the check for an equal entry
     */
    public function addUrl(
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
        if (is_array($subCfg['procInstrParams.'] ?? false)) {
            $parameters['procInstrParams'] = $subCfg['procInstrParams.'];
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
            'set_id' => $this->setId,
            'result_data' => '',
            'configuration' => $subCfg['key'],
        ];

        if ($this->registerQueueEntriesInternallyOnly) {
            //the entries will only be registered and not stored to the database
            $this->queueEntries[] = $fieldArray;
        } else {
            if (!$skipInnerDuplicationCheck) {
                // check if there is already an equal entry
                $rows = $this->queueRepository->getDuplicateQueueItemsIfExists(
                    (bool) $this->extensionSettings['enableTimeslot'],
                    $scheduled,
                    $this->getCurrentTime(),
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
     * Returns the current system time
     */
    public function getCurrentTime(): int
    {
        return time();
    }

    /**
     * Returns a md5 hash generated from a serialized configuration array.
     */
    protected function getConfigurationHash(array $configuration): string
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
            $scheduled - $this->getCurrentTime()
        );
    }
}
