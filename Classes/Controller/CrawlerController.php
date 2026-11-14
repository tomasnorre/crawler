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

use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Value\QueueRow;
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
    public array $duplicateTrack = [];
    public array $downloadUrls = [];
    public array $incomingProcInstructions = [];
    public array $incomingConfigurationSelection = [];

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
        private readonly PageRepository $pageRepository,
        protected readonly IconFactory $iconFactory,
        private readonly ConfigurationService $configurationService,
        private readonly PageConfigurationResolver $pageConfigurationResolver,
        private readonly QueueEntryWriter $queueEntryWriter,
    ) {
    }

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

    private function addConfigurationToQueueRow(QueueRow $queueRow, array $pageRow, array $configuration): void
    {
        $excludedPages = $this->configurationService->expandExcludeString($configuration['subCfg']['exclude'] ?? '');
        if (in_array($pageRow['uid'], $excludedPages, true)) {
            $queueRow->setMessage('(Page is excluded in this configuration)');
            return;
        }

        $queueRow->setUrls($this->queueEntryWriter->compileUrlList(
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
