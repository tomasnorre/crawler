<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Repository;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @internal since v9.2.5
 */
class ConfigurationRepository extends Repository
{
    public const TABLE_NAME = 'tx_crawler_configuration';

    public function getCrawlerConfigurationRecords(): array
    {
        $records = [];
        $queryBuilder = $this->createQueryBuilder();
        $statement = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->execute();

        while ($row = $statement->fetch()) {
            $records[] = $row;
        }

        return $records;
    }

    /**
     * Traverses up the rootline of a page and fetches all crawler records.
     */
    public function getCrawlerConfigurationRecordsFromRootLine(int $pageId, array $parentIds = []): array
    {
        if (empty($parentIds)) {
            $pageIdsInRootLine = [];
            $rootLine = BackendUtility::BEgetRootLine($pageId);

            foreach ($rootLine as $pageInRootLine) {
                $pageIdsInRootLine[] = (int) $pageInRootLine['uid'];
            }
        } else {
            $pageIdsInRootLine = $parentIds;
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        return $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pageIdsInRootLine, Connection::PARAM_INT_ARRAY))
            )
            ->orderBy('name')
            ->execute()
            ->fetchAll();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
    }
}
