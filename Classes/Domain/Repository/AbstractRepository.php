<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class AbstractRepository
 *
 * @package AOE\Crawler\Domain\Repository
 */
abstract class AbstractRepository extends Repository
{
    /**
     * @var string table name
     */
    protected $tableName;

    /**
     * Counts all in repository
     *
     * @return integer
     *
     * Todo: Remove as the Repository function that we enherit from already have a countAll() function. But currently tests fails, if removed.
     */
    public function countAll()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * @param string $processId
     *
     * @return bool|string
     */
    public function countAllByProcessId($processId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        return $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('process_id', $queryBuilder->createNamedParameter($processId, \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchColumn(0);
    }
}
