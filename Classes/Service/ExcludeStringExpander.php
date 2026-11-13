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

use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Expands the "exclude" setting of a crawler configuration, fx. "1,5+2,7+",
 * to the page ids it covers.
 *
 * @internal
 */
class ExcludeStringExpander
{
    private const DEFAULT_SUBPAGE_DEPTH = 99;

    /**
     * Page ids per exclude string, keys are kept from array_unique()
     *
     * @var array<string, array<int, int>>
     */
    private array $expandedExcludeStringCache = [];

    /**
     * PageTreeView::$tree per page id and depth
     *
     * @var array<int, array<int, array>>
     */
    private array $pageTreeCache = [];

    /**
     * Each comma separated part is a page id, optionally followed by "+" and the depth of subpages
     * to include. "5" is page 5 only, "5+2" adds two levels of subpages, "5+" adds all subpages.
     *
     * @return array<int, int>
     */
    public function expand(string $excludeString): array
    {
        if (isset($this->expandedExcludeStringCache[$excludeString])) {
            return $this->expandedExcludeStringCache[$excludeString];
        }

        $pidList = [];
        foreach (GeneralUtility::trimExplode(',', $excludeString, true) as $excludePart) {
            [$pid, $depth] = $this->parseExcludePart($excludePart);
            $pidList[] = $pid;
            if ($depth > 0) {
                $pidList = $this->addSubpages($pid, $depth, $pidList);
            }
        }

        $this->expandedExcludeStringCache[$excludeString] = array_unique($pidList);

        return $this->expandedExcludeStringCache[$excludeString];
    }

    /**
     * @return array{int, int} The page id and the depth of subpages
     */
    private function parseExcludePart(string $excludePart): array
    {
        $parts = GeneralUtility::trimExplode('+', $excludePart);
        $depth = (int) ($parts[1] ?? 0);
        if ($depth === 0 && str_contains($excludePart, '+')) {
            $depth = self::DEFAULT_SUBPAGE_DEPTH;
        }

        return [(int) $parts[0], $depth];
    }

    /**
     * @param list<int> $pidList
     * @return list<int>
     */
    private function addSubpages(int $pid, int $depth, array $pidList): array
    {
        if (!isset($this->pageTreeCache[$pid][$depth])) {
            // A new tree per page, as getTree() appends to the tree of an existing instance
            $tree = GeneralUtility::makeInstance(PageTreeView::class);
            $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
            $tree->getTree($pid, $depth);
            $this->pageTreeCache[$pid][$depth] = $tree->tree;
        }

        foreach ($this->pageTreeCache[$pid][$depth] as $data) {
            $pidList[] = (int) $data['row']['uid'];
        }
        return $pidList;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        return $GLOBALS['BE_USER'];
    }
}
