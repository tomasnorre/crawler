<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\ProcessInstructionService;
use AOE\Crawler\Service\UrlService;
use AOE\Crawler\Value\QueueRow;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package AOE\Crawler\Controller
 * @internal since v12.0.0
 */
class CrawlerController
{
    public int $setID = 0;
    public array $duplicateTrack = [];
    public array $downloadUrls = [];
    public array $incomingProcInstructions = [];
    public array $incomingConfigurationSelection = [];
    public bool $registerQueueEntriesInternallyOnly = false;
    public array $queueEntries = [];
    public array $urlList = [];
    public array $extensionSettings = [];

    /**
     * Mount Point
     */
    public ?string $MP = null;

    /**
     * @var BackendUserAuthentication|null
     */
    private $backendUser;
    private int $scheduledTime = 0;
    private int $reqMinute = 0;
    private bool $submitCrawlUrls = false;
    private bool $downloadCrawlUrls = false;

    public function __construct(
        protected readonly QueueRepository $queueRepository,
        private readonly PageRepository $pageRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        protected readonly IconFactory $iconFactory,
        private readonly ConfigurationService $configurationService,
        private readonly PageConfigurationResolver $pageConfigurationResolver,
        private readonly UrlService $urlService,
        private readonly ProcessInstructionService $processInstructionService,
        private readonly CrawlUrlMessageDispatcher $crawlUrlMessageDispatcher,
        ExtensionConfigurationProvider $extensionConfigurationProvider,
    ) {
        $this->extensionSettings = $extensionConfigurationProvider->getExtensionConfiguration();
    }

    /**
     * Creates a list of URLs from input array (and submits them to queue if asked for)
     * See Web > Info module script + "indexed_search"'s crawler hook-client using this!
     *
     * @param array $vv Information about URLs from pageRow to crawl.
     * @param array $pageRow Page row
     * @param int $scheduledTime Unix time to schedule indexing to, typically time()
     * @param int $reqMinute Number of requests per minute (creates the interleave between requests)
     * @param bool $submitCrawlUrls If set, submits the URLs to queue
     * @param bool $downloadCrawlUrls If set (and submitcrawlUrls is false) will fill $downloadUrls with entries)
     * @param array $duplicateTrack Array which is passed by reference and contains the an id per url to secure we will not crawl duplicates
     * @param array $downloadUrls Array which will be filled with URLS for download if flag is set.
     * @param array $incomingProcInstructions Array of processing instructions
     * @return string List of URLs (meant for display in backend module)
     */
    public function urlListFromUrlArray(
        array $vv,
        array $pageRow,
        int $scheduledTime,
        int $reqMinute,
        bool $submitCrawlUrls,
        bool $downloadCrawlUrls,
        array &$duplicateTrack,
        array &$downloadUrls,
        array $incomingProcInstructions
    ): string {
        if (!is_array($vv['URLs'])) {
            return 'ERROR - no URL generated';
        }
        $urlLog = [];
        $pageId = (int) $pageRow['uid'];
        $configurationHash = $this->getConfigurationHash($vv);
        $skipInnerCheck = $this->queueRepository->noUnprocessedQueueEntriesForPageWithConfigurationHashExist(
            $pageId,
            $configurationHash
        );

        foreach ($vv['URLs'] as $urlQuery) {
            if (!$this->processInstructionService->isAllowed(
                $vv['subCfg']['procInstrFilter'] ?? '',
                $incomingProcInstructions
            )) {
                continue;
            }
            $url = $this->urlService->getUrlFromPageAndQueryParameters(
                $pageId,
                $urlQuery,
                $vv['subCfg']['baseUrl'] ?? null,
                (int) ($vv['subCfg']['force_ssl'] ?? 0)
            );

            if (!$url instanceof UriInterface) {
                continue;
            }

            $url = (string) $url;

            // Create key by which to determine unique-ness:
            $uKey = $url . '|' . ($vv['subCfg']['userGroups'] ?? '') . '|' . ($vv['subCfg']['procInstrFilter'] ?? '');

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
                        $vv['subCfg'],
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

    /************************************
     *
     * URL setting
     *
     ************************************/

    /**
     * Setting a URL for crawling:
     *
     * @param integer $id Page ID
     * @param string $url Complete URL
     * @param array $subCfg Sub configuration array (from TS config)
     * @param integer $tstamp Scheduled-time
     * @param string $configurationHash (optional) configuration hash
     * @param bool $skipInnerDuplicationCheck (optional) skip inner duplication check
     * @return bool
     */
    public function addUrl(
        $id,
        $url,
        array $subCfg,
        $tstamp,
        $configurationHash = '',
        $skipInnerDuplicationCheck = false
    ) {
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
            'page_id' => (int) $id,
            'parameters' => $parameters_serialized,
            'parameters_hash' => md5($parameters_serialized),
            'configuration_hash' => $configurationHash,
            'scheduled' => $tstamp,
            'exec_time' => 0,
            'set_id' => $this->setID,
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
                    $tstamp,
                    $this->getCurrentTime(),
                    $fieldArray['page_id'],
                    $fieldArray['parameters_hash']
                );
            }
            if ($rows === []) {
                $uid = $this->queueRepository->add($fieldArray);
                $urlAdded = true;

                $this->eventDispatcher->dispatch(new AfterUrlAddedToQueueEvent((string) $uid, $fieldArray));
                $this->dispatchCrawlUrlMessage($uid, (int) $id, (int) $tstamp);
            }
        }

        return $urlAdded;
    }

    /**
     * Returns the current system time
     *
     * @return int
     */
    public function getCurrentTime()
    {
        return time();
    }

    /************************************
     *
     * URL reading
     *
     ************************************/
    /*****************************
     *
     * Compiling URLs to crawl - tools
     *
     *****************************/

    /**
     * This draws the pageTree with URLs for e.g the Backend Log Module
     *
     * @param integer $id Root page id to start from.
     * @param integer $depth Depth of tree, 0=only id-page, 1= on sublevel, 99 = infinite
     * @param integer $scheduledTime Unix Time when the URL is timed to be visited when put in queue
     * @param integer $reqMinute Number of requests per minute (creates the interleave between requests)
     * @param boolean $submitCrawlUrls If set, submits the URLs to queue in database (real crawling)
     * @param boolean $downloadCrawlUrls If set (and submitcrawlUrls is false) will fill $downloadUrls with entries)
     * @param array $incomingProcInstructions Array of processing instructions
     * @param array $configurationSelection Array of configuration keys
     * @return array
     */
    public function getPageTreeAndUrls(
        $id,
        $depth,
        $scheduledTime,
        $reqMinute,
        $submitCrawlUrls,
        $downloadCrawlUrls,
        array $incomingProcInstructions,
        array $configurationSelection
    ) {
        $this->scheduledTime = $scheduledTime;
        $this->reqMinute = $reqMinute;
        $this->submitCrawlUrls = $submitCrawlUrls;
        $this->downloadCrawlUrls = $downloadCrawlUrls;
        $this->incomingProcInstructions = $incomingProcInstructions;
        $this->incomingConfigurationSelection = $configurationSelection;

        $this->duplicateTrack = [];
        $this->downloadUrls = [];

        // Drawing tree:
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $tree->init(empty($perms_clause) ? '' : ('AND ' . $perms_clause));

        $pageInfo = BackendUtility::readPageAccess($id, $perms_clause);
        if (is_array($pageInfo)) {
            // Set root row:
            $tree->tree[] = [
                'row' => $pageInfo,
                'HTML' => $this->iconFactory->getIconForRecord('pages', $pageInfo, IconSize::SMALL),
            ];
        }

        // Get branch beneath:
        if ($depth) {
            $tree->getTree($id, $depth, '');
        }

        $queueRows = [];

        // Traverse page tree:
        foreach ($tree->tree as $data) {
            $this->MP = null;

            // recognize mount points
            if ($data['row']['doktype'] === PageRepository::DOKTYPE_MOUNTPOINT) {
                $mountpage = $this->pageRepository->getPage($data['row']['uid']);

                // fetch mounted pages
                $this->MP = $mountpage['mount_pid'] . '-' . $data['row']['uid'];

                $mountTree = GeneralUtility::makeInstance(PageTreeView::class);
                $mountTree->init(empty($perms_clause) ? '' : ('AND ' . $perms_clause));
                $mountTree->getTree($mountpage['mount_pid'], $depth);

                foreach ($mountTree->tree as $mountData) {
                    $queueRows = array_merge($queueRows, $this->drawURLs_addRowsForPage(
                        $mountData['row'],
                        BackendUtility::getRecordTitle('pages', $mountData['row'], true),
                        (string) $data['HTML']
                    ));
                }

                // replace page when mount_pid_ol is enabled
                if ($mountpage['mount_pid_ol']) {
                    $data['row']['uid'] = $mountpage['mount_pid'];
                } else {
                    // if the mount_pid_ol is not set the MP must not be used for the mountpoint page
                    $this->MP = null;
                }
            }

            $queueRows = array_merge($queueRows, $this->drawURLs_addRowsForPage(
                $data['row'],
                BackendUtility::getRecordTitle('pages', $data['row'], true),
                (string) $data['HTML']
            ));
        }

        return $queueRows;
    }

    /**
     * Create the rows for display of the page tree
     * For each page a number of rows are shown displaying GET variable configuration
     *
     * @return list<QueueRow>
     */
    public function drawURLs_addRowsForPage(array $pageRow, string $pageTitle, string $pageTitleHTML = ''): array
    {
        $skipMessage = '';
        $configurations = ConfigurationService::removeDisallowedConfigurations(
            $this->incomingConfigurationSelection,
            $this->pageConfigurationResolver->getConfigurationsForPageRow($pageRow, $skipMessage, $this->MP ?? '')
        );

        if ($configurations === []) {
            $queueRow = new QueueRow($pageTitle);
            $queueRow->setPageTitleHTML($pageTitleHTML);
            $queueRow->setMessage($skipMessage !== '' ? ' (' . $skipMessage . ')' : '');
            return [$queueRow];
        }

        $queueRowCollection = [];
        foreach ($configurations as $configurationKey => $configuration) {
            // Only the first row of a page shows the page title
            $queueRow = new QueueRow($queueRowCollection === [] ? $pageTitle : '');
            $queueRow->setPageTitleHTML($pageTitleHTML);
            $queueRow->setConfigurationKey((string) $configurationKey);
            $this->addConfigurationToQueueRow($queueRow, $pageRow, $configuration);
            $queueRowCollection[] = $queueRow;
        }

        return $queueRowCollection;
    }

    /**
     * Returns a md5 hash generated from a serialized configuration array.
     *
     * @return string
     */
    protected function getConfigurationHash(array $configuration)
    {
        unset($configuration['paramExpanded']);
        unset($configuration['URLs']);
        return md5(serialize($configuration));
    }

    private function addConfigurationToQueueRow(QueueRow $queueRow, array $pageRow, array $configuration): void
    {
        $excludedPages = $this->configurationService->expandExcludeString($configuration['subCfg']['exclude'] ?? '');
        if (in_array($pageRow['uid'], $excludedPages, true)) {
            $queueRow->setMessage('(Page is excluded in this configuration)');
            return;
        }

        $queueRow->setUrls($this->urlListFromUrlArray(
            $configuration,
            $pageRow,
            $this->scheduledTime,
            $this->reqMinute,
            $this->submitCrawlUrls,
            $this->downloadCrawlUrls,
            $this->duplicateTrack,
            $this->downloadUrls,
            // if empty the urls won't be filtered by processing instructions
            $this->incomingProcInstructions
        ));
        $queueRow->setValuesExpanded($this->renderExpandedParameters($configuration['paramExpanded']));
        $queueRow->setOptions($this->getQueueRowOptions($configuration['subCfg']));
        $queueRow->setParameters(DebugUtility::viewArray($configuration['subCfg']['procInstrParams.'] ?? []));
        $queueRow->setParameterConfig($this->renderParameterConfig($configuration['paramParsed'] ?? []));
    }

    /**
     * A table with the values of each parameter, followed by the number of combinations, fx. "Comb: 2*3=6"
     */
    private function renderExpandedParameters(array $paramExpanded): string
    {
        $tableRows = '';
        $valueCounts = [];
        foreach ($paramExpanded as $parameter => $values) {
            $tableRows .= '
                            <tr>
                                <td>' . htmlspecialchars('&' . $parameter . '=') . '<br/>(' . count($values) . ')</td>
                                <td nowrap="nowrap">' . nl2br(htmlspecialchars(implode(chr(10), $values))) . '</td>
                            </tr>
                        ';
            $valueCounts[] = count($values);
        }

        $table = $tableRows !== '' ? '<table>' . $tableRows . '</table>' : '';
        return $table . 'Comb: ' . implode('*', $valueCounts) . '=' . array_product($valueCounts);
    }

    /**
     * @return list<string>
     */
    private function getQueueRowOptions(array $subConfiguration): array
    {
        $options = [];
        if ($subConfiguration['userGroups'] ?? false) {
            $options[] = 'User Groups: ' . $subConfiguration['userGroups'];
        }
        if ($subConfiguration['procInstrFilter'] ?? false) {
            $options[] = 'ProcInstr: ' . $subConfiguration['procInstrFilter'];
        }
        return $options;
    }

    /**
     * The parsed parameters, one per line, fx. "&L=[0|1]"
     */
    private function renderParameterConfig(array $paramParsed): string
    {
        $parameters = GeneralUtility::implodeArrayForUrl('', $paramParsed);
        return nl2br(htmlspecialchars(rawurldecode(trim(str_replace('&', chr(10) . '&', $parameters)))));
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

    /**
     * @return BackendUserAuthentication
     */
    private function getBackendUser()
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        if ($this->backendUser === null) {
            $this->backendUser = $GLOBALS['BE_USER'];
        }
        return $this->backendUser;
    }
}
