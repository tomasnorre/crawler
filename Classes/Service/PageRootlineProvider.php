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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Reads the rootline of a page with the crawler exclusion fields of the page properties.
 *
 * Not final, so tests of its users can replace it, as it reads the rootline from the database.
 *
 * @internal
 */
class PageRootlineProvider
{
    public const FIELD_EXCLUDE = 'tx_crawler_exclude';
    public const FIELD_EXCLUDE_SUBPAGES = 'tx_crawler_exclude_subpages';

    /**
     * Returns the page and its parent pages, starting with the page itself and ending with the root page.
     * Pages that do not exist (anymore) end the rootline.
     *
     * @return list<array{uid: int, tx_crawler_exclude: bool, tx_crawler_exclude_subpages: bool}>
     */
    public function getRootlineWithExclusionFields(int $pageId): array
    {
        $rootline = BackendUtility::BEgetRootLine(
            $pageId,
            '',
            false,
            [self::FIELD_EXCLUDE, self::FIELD_EXCLUDE_SUBPAGES]
        );
        // BEgetRootLine() keys the rootline by its depth, the root page has the key 0
        krsort($rootline);

        $pages = [];
        foreach ($rootline as $page) {
            if ((int) ($page['uid'] ?? 0) === 0) {
                continue;
            }
            $pages[] = [
                'uid' => (int) $page['uid'],
                self::FIELD_EXCLUDE => (bool) ($page[self::FIELD_EXCLUDE] ?? false),
                self::FIELD_EXCLUDE_SUBPAGES => (bool) ($page[self::FIELD_EXCLUDE_SUBPAGES] ?? false),
            ];
        }
        return $pages;
    }
}
