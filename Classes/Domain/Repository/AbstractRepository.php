<?php
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

use TYPO3\CMS\Core\Database\Query\QueryBuilder;


/**
 * Class AbstractRepository
 *
 * @package AOE\Crawler\Domain\Repository
 */
abstract class AbstractRepository
{

    /**
     * @var string table name
     */
    protected $tableName;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder = QueryBuilder::class;

    /**
     * Counts all in repository
     *
     * @return integer
     */
    public function countAll()
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * @param $processId
     *
     * @return bool|string
     */
    public function countAllByProcessId($processId)
    {
        $count = $this->queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('process_id', $this->queryBuilder->createNamedParameter($processId))
            )
            ->execute()
            ->fetchColumn(0);

        return $count;
    }

    /**
     * @param int $processId
     */
    public function removeByProcessId($processId)
    {
        $this->queryBuilder
            ->delete($this->tableName)
            ->where(
                $this->queryBuilder->expr()->eq('process_id', $this->queryBuilder->createNamedParameter($processId))
            )
            ->execute();
    }

    /**
     * Returns an instance of the TYPO3 database class.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     * @deprecated since crawler v7.0.0, will be removed in crawler v8.0.0.
     */
    protected function getDB()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
