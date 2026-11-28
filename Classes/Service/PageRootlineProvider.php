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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

/**
 * Reads the rootline of a page with the crawler exclusion fields of the page properties.
 *
 * The rootline is read from the database every time, instead of using BackendUtility::BEgetRootLine(),
 * which keeps it in the runtime cache: the message bus worker runs for a long time, and the exclusion
 * of a page can change in the meantime.
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
     * Protects against loops in the page tree
     */
    private const MAX_DEPTH = 100;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * Returns the page and its parent pages, starting with the page itself and ending with the root page.
     * Pages that do not exist (anymore) end the rootline.
     *
     * @return list<array{uid: int, tx_crawler_exclude: bool, tx_crawler_exclude_subpages: bool}>
     */
    public function getRootlineWithExclusionFields(int $pageId): array
    {
        $pages = [];
        while ($pageId > 0 && count($pages) < self::MAX_DEPTH) {
            $page = $this->getPage($pageId);
            if ($page === false) {
                break;
            }
            $pages[] = [
                'uid' => (int) $page['uid'],
                self::FIELD_EXCLUDE => (bool) $page[self::FIELD_EXCLUDE],
                self::FIELD_EXCLUDE_SUBPAGES => (bool) $page[self::FIELD_EXCLUDE_SUBPAGES],
            ];
            $pageId = (int) $page['pid'];
        }
        return $pages;
    }

    /**
     * @return array<string, mixed>|false
     */
    private function getPage(int $pageId): array|false
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        return $queryBuilder
            ->select('uid', 'pid', self::FIELD_EXCLUDE, self::FIELD_EXCLUDE_SUBPAGES)
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
    }
}
