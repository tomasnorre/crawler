<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Repository;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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
 * Class ConfigurationRepository
 */
class ConfigurationRepository extends Repository
{
    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_configuration';

    public function getCrawlerConfigurationRecords(): array
    {
        $records = [];
        $queryBuilder = $this->createQueryBuilder();
        $statement = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->execute();

        while ($row = $statement->fetch()) {
            $records[] = $row;
        }

        return $records;
    }

    /**
     * Traverses up the rootline of a page and fetches all crawler records.
     */
    public function getCrawlerConfigurationRecordsFromRootLine(int $pageId): array
    {
        $pageIdsInRootLine = [];
        $rootLine = BackendUtility::BEgetRootLine($pageId);

        foreach ($rootLine as $pageInRootLine) {
            $pageIdsInRootLine[] = (int) $pageInRootLine['uid'];
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $configurationRecordsForCurrentPage = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pageIdsInRootLine, Connection::PARAM_INT_ARRAY))
            )
            ->execute()
            ->fetchAll();
        return is_array($configurationRecordsForCurrentPage) ? $configurationRecordsForCurrentPage : [];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
    }
}
