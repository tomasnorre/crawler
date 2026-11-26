<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

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

use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Queue\QueueEntryWriter;
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
 * Walks the page tree and collects the URLs of the crawler configurations of each page,
 * as rows for display in the backend module and the CLI. Depending on the options of the CrawlRun,
 * the URLs are added to the queue or collected for download.
 *
 * @internal
 */
final class PageTreeUrlCollector
{
    /**
     * @var BackendUserAuthentication|null
     */
    private $backendUser;

    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly IconFactory $iconFactory,
        private readonly ConfigurationService $configurationService,
        private readonly PageConfigurationResolver $pageConfigurationResolver,
        private readonly QueueEntryWriter $queueEntryWriter,
    ) {
    }

    /**
     * Returns the rows of the page tree with their URLs, fx. for the backend module.
     * The URLs are handled according to the options of the run, which also collects them.
     *
     * @param int $id Root page id to start from
     * @param int $depth Depth of tree, 0 = only the root page, 1 = one sublevel, 99 = infinite
     * @return list<QueueRow>
     */
    public function collect(int $id, int $depth, CrawlRun $run): array
    {
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
            $mountPoint = '';

            // recognize mount points
            if ($data['row']['doktype'] === PageRepository::DOKTYPE_MOUNTPOINT) {
                $mountpage = $this->pageRepository->getPage($data['row']['uid']);

                // fetch mounted pages
                $mountPoint = $mountpage['mount_pid'] . '-' . $data['row']['uid'];

                $mountTree = GeneralUtility::makeInstance(PageTreeView::class);
                $mountTree->init(empty($perms_clause) ? '' : ('AND ' . $perms_clause));
                $mountTree->getTree($mountpage['mount_pid'], $depth);

                foreach ($mountTree->tree as $mountData) {
                    $queueRows = array_merge($queueRows, $this->getQueueRowsForPage(
                        $run,
                        $mountData['row'],
                        BackendUtility::getRecordTitle('pages', $mountData['row'], true),
                        (string) $data['HTML'],
                        $mountPoint
                    ));
                }

                // replace page when mount_pid_ol is enabled
                if ($mountpage['mount_pid_ol']) {
                    $data['row']['uid'] = $mountpage['mount_pid'];
                } else {
                    // if the mount_pid_ol is not set the mount point must not be used for the mountpoint page
                    $mountPoint = '';
                }
            }

            $queueRows = array_merge($queueRows, $this->getQueueRowsForPage(
                $run,
                $data['row'],
                BackendUtility::getRecordTitle('pages', $data['row'], true),
                (string) $data['HTML'],
                $mountPoint
            ));
        }

        return $queueRows;
    }

    /**
     * Returns the rows of a page, one per configuration showing its URLs and parameters
     *
     * @param string $mountPoint The mount point the page is shown in, fx. "3-7", or empty
     * @return list<QueueRow>
     */
    public function getQueueRowsForPage(
        CrawlRun $run,
        array $pageRow,
        string $pageTitle,
        string $pageTitleHTML = '',
        string $mountPoint = ''
    ): array {
        $skipMessage = $this->pageConfigurationResolver->getSkipReason($pageRow);
        if ($skipMessage !== '') {
            return [$this->createRowWithoutConfiguration($pageTitle, $pageTitleHTML, ' (' . $skipMessage . ')')];
        }

        $configurations = ConfigurationService::removeDisallowedConfigurations(
            $run->configurationSelection,
            $this->pageConfigurationResolver->getConfigurationsForPage((int) $pageRow['uid'], $mountPoint)
        );
        if ($configurations === []) {
            return [$this->createRowWithoutConfiguration($pageTitle, $pageTitleHTML, '')];
        }

        $queueRowCollection = [];
        foreach ($configurations as $configurationKey => $configuration) {
            // Only the first row of a page shows the page title
            $queueRow = new QueueRow($queueRowCollection === [] ? $pageTitle : '');
            $queueRow->setPageTitleHTML($pageTitleHTML);
            $queueRow->setConfigurationKey((string) $configurationKey);
            $this->addConfigurationToQueueRow($queueRow, $pageRow, $configuration, $run);
            $queueRowCollection[] = $queueRow;
        }

        return $queueRowCollection;
    }

    private function createRowWithoutConfiguration(string $pageTitle, string $pageTitleHTML, string $message): QueueRow
    {
        $queueRow = new QueueRow($pageTitle);
        $queueRow->setPageTitleHTML($pageTitleHTML);
        $queueRow->setMessage($message);
        return $queueRow;
    }

    private function addConfigurationToQueueRow(
        QueueRow $queueRow,
        array $pageRow,
        array $configuration,
        CrawlRun $run
    ): void {
        $excludedPages = $this->configurationService->expandExcludeString($configuration['subCfg']['exclude'] ?? '');
        if (in_array($pageRow['uid'], $excludedPages, true)) {
            $queueRow->setMessage('(Page is excluded in this configuration)');
            return;
        }

        $queueRow->setUrls($this->queueEntryWriter->compileUrlList($configuration, $pageRow, $run));
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
        $this->backendUser ??= $GLOBALS['BE_USER'];
        return $this->backendUser;
    }
}
