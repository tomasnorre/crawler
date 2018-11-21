<?php
namespace AOE\Crawler\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Domain\Model\Process;
use AOE\Crawler\Domain\Model\ProcessCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ProcessRepository
 *
 * @package AOE\Crawler\Domain\Repository
 */
class ProcessRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_process';

    /**
     * This method is used to find all cli processes within a limit.
     *
     * @param  string $orderField
     * @param  string $orderDirection
     * @param  integer $itemCount
     * @param  integer $offset
     * @param  string $where
     *
     * @return ProcessCollection
     */
    public function findAll($orderField = '', $orderDirection = 'DESC', $itemCount = null, $offset = null, $where = '')
    {
        /** @var ProcessCollection $collection */
        $collection = GeneralUtility::makeInstance(ProcessCollection::class);

        $orderField = trim($orderField);
        $orderField = empty($orderField) ? 'process_id' : $orderField;

        $orderDirection = strtoupper(trim($orderDirection));
        $orderDirection = in_array($orderDirection, ['ASC', 'DESC']) ? $orderDirection : 'DESC';

        $rows = $this->getDB()->exec_SELECTgetRows(
            '*',
            $this->tableName,
            $where,
            '',
            htmlspecialchars($orderField) . ' ' . htmlspecialchars($orderDirection),
            self::getLimitFromItemCountAndOffset($itemCount, $offset)
        );

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $process = new Process();
                $process->setProcessId($row['process_id']);
                $collection->append($process);
            }
        }

        return $collection;
    }

    /**
     * This method is used to count all processes in the process table.
     *
     * @param  string $where Where clause
     *
     * @return integer
     */
    public function countAll($where = '1 = 1')
    {
        return $this->countByWhere($where);
    }

    /**
     * Returns the number of active processes.
     *
     * @return integer
     */
    public function countActive()
    {
        return $this->countByWhere('active = 1 AND deleted = 0');
    }

    /**
     * Returns the number of processes that live longer than the given timestamp.
     *
     * @param  integer $ttl
     *
     * @return integer
     */
    public function countNotTimeouted($ttl)
    {
        return $this->countByWhere('deleted = 0 AND ttl > ' . intval($ttl));
    }

    /**
     * Get limit clause
     *
     * @param  integer $itemCount
     * @param  integer $offset
     *
     * @return string
     */
    public static function getLimitFromItemCountAndOffset($itemCount, $offset)
    {
        $itemCount = filter_var($itemCount, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 20]]);
        $offset = filter_var($offset, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'default' => 0]]);
        $limit = $offset . ', ' . $itemCount;

        return $limit;
    }
}
