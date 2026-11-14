<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

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

use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Finds the crawler configurations of pages, from page TSconfig and tx_crawler_configuration records.
 *
 * Not final, so tests of its users can replace it, as it reads page TSconfig from the database.
 *
 * @internal
 */
class PageConfigurationResolver
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly ConfigurationService $configurationService,
        private readonly ConfigurationRepository $configurationRepository,
    ) {
    }

    /**
     * Returns the configurations of the page. If the page is not crawled, it returns an empty array
     * and sets the reason in $skipMessage.
     *
     * @param array $pageRow Page record with at least the doktype and uid columns
     * @param string $mountPoint The mount point as "<mounted page>-<mount point page>", fx. "5-12"
     */
    public function getConfigurationsForPageRow(
        array $pageRow,
        string &$skipMessage = '',
        string $mountPoint = ''
    ): array {
        if (!isset($pageRow['uid'])) {
            $skipMessage = "pageRow['uid'] is missing";
            return [];
        }

        $pageId = (int) $pageRow['uid'];
        if ($pageId === 0) {
            $skipMessage = 'PageUid "' . $pageRow['uid'] . '" was not an integer';
            return [];
        }

        $message = $this->pageService->checkIfPageShouldBeSkipped($pageRow);
        if ($message !== false) {
            $skipMessage = $message;
            return [];
        }

        $skipMessage = '';
        return $this->getConfigurationsForPage($pageId, $mountPoint);
    }

    /**
     * Returns the configurations of the page, without checking if the page should be crawled.
     *
     * @param string $mountPoint The mount point as "<mounted page>-<mount point page>", fx. "5-12"
     */
    public function getConfigurationsForPage(int $pageId, string $mountPoint = ''): array
    {
        $pageTSconfig = $this->getPageTSconfig($pageId, $mountPoint);

        $configurations = $this->configurationService->getConfigurationFromPageTS(
            $pageTSconfig,
            $pageId,
            [],
            $mountPoint
        );
        // Configuration records up the rootline
        $configurations = $this->configurationService->getConfigurationFromDatabase($pageId, $configurations);

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['processUrls'] ?? [] as $func) {
            $params = [
                'res' => &$configurations,
            ];
            GeneralUtility::callUserFunction($func, $params, $this);
        }
        return $configurations;
    }

    /**
     * Returns the names of the configurations of the page and its subpages.
     *
     * @return list<string>
     * @psalm-api Used by BackendModuleStartCrawlingController, which only the backend routing calls
     */
    public function getConfigurationNamesForBranch(int $rootId, int $depth): array
    {
        $configurationNames = [];
        $paramSets = $this->getPageTSconfig($rootId)['tx_crawler.']['crawlerCfg.']['paramSets.'] ?? [];
        foreach ($paramSets as $key => $value) {
            if (is_array($value)) {
                $key = (string) $key;
                $configurationNames[] = str_ends_with($key, '.') ? substr($key, 0, -1) : $key;
            }
        }

        $configurations = $this->configurationRepository->getCrawlerConfigurationRecordsFromRootLine(
            $rootId,
            [...$this->getRootlinePageIds($rootId), ...$this->getSubpageIds($rootId, $depth)]
        );
        foreach ($configurations as $configuration) {
            $configurationNames[] = (string) $configuration['name'];
        }
        return $configurationNames;
    }

    private function getPageTSconfig(int $pageId, string $mountPoint = ''): array
    {
        if ($mountPoint === '') {
            $pageTSconfig = BackendUtility::getPagesTSconfig($pageId);
        } else {
            [, $mountPointPageId] = explode('-', $mountPoint);
            $pageTSconfig = BackendUtility::getPagesTSconfig((int) $mountPointPageId);
        }

        // Call a hook to alter configuration
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'] ?? null;
        if (is_array($hooks)) {
            $params = [
                'pageId' => $pageId,
                'pageTSConfig' => &$pageTSconfig,
            ];
            foreach ($hooks as $userFunc) {
                GeneralUtility::callUserFunction($userFunc, $params, $this);
            }
        }
        return $pageTSconfig;
    }

    /**
     * @return list<int>
     */
    private function getRootlinePageIds(int $pageId): array
    {
        return array_map(
            static fn (array $page): int => (int) $page['uid'],
            array_values(BackendUtility::BEgetRootLine($pageId))
        );
    }

    /**
     * @return list<int>
     */
    private function getSubpageIds(int $pageId, int $depth): array
    {
        $permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init(empty($permsClause) ? '' : ('AND ' . $permsClause));
        $tree->getTree($pageId, $depth, '');

        return array_map(static fn (array $node): int => (int) $node['row']['uid'], array_values($tree->tree));
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        return $GLOBALS['BE_USER'];
    }
}
