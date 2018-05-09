<?php
namespace AOE\Crawler\Domain\Model;

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

use AOE\Crawler\Domain\Repository\QueueRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class Process
 *
 * @package AOE\Crawler\Domain\Model
 */
class Process
{
    const STATE_RUNNING = 'running';
    const STATE_CANCELLED = 'cancelled';
    const STATE_COMPLETED = 'completed';

    /**
     * @var array
     */
    protected $row;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @param array $row
     */
    public function __construct($row = [])
    {
        $this->row = $row;
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
    }

    /**
     * Returns the activity state for this process
     *
     * @param void
     * @return boolean
     */
    public function getActive()
    {
        return $this->row['active'];
    }

    /**
     * Returns the identifier for the process
     *
     * @return string
     */
    public function getProcess_id()
    {
        return $this->row['process_id'];
    }

    /**
     * Returns the timestamp of the exectime for the first relevant queue item.
     * This can be used to determine the runtime
     *
     * @return int
     */
    public function getTimeForFirstItem()
    {
        $entry = $this->queueRepository->findYoungestEntryForProcess($this);
        return $entry->getExecutionTime();
    }

    /**
     * Returns the timestamp of the exectime for the last relevant queue item.
     * This can be used to determine the runtime
     *
     * @return int
     */
    public function getTimeForLastItem()
    {
        $entry = $this->queueRepository->findOldestEntryForProcess($this);
        return $entry->getExecutionTime();
    }

    /**
     * Returns the difference between first and last processed item
     *
     * @return int
     */
    public function getRuntime()
    {
        return $this->getTimeForLastItem() - $this->getTimeForFirstItem();
    }

    /**
     * Returns the ttl of the process
     *
     * @return int
     */
    public function getTTL()
    {
        return $this->row['ttl'];
    }

    /**
     * Counts the number of items which need to be processed
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function countItemsProcessed()
    {
        return $this->queueRepository->countExecutedItemsByProcess($this);
    }

    /**
     * Counts the number of items which still need to be processed
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function countItemsToProcess()
    {
        return $this->queueRepository->countNonExecutedItemsByProcess($this);
    }

    /**
     * Returns the Progress of a crawling process as a percentage value
     *
     * @return float
     */
    public function getProgress()
    {
        $all = $this->countItemsAssigned();
        if ($all <= 0) {
            return 0;
        }

        $res = round((100 / $all) * $this->countItemsProcessed());

        if ($res > 100.0) {
            return 100.0;
        }
        return $res;
    }

    /**
     * Returns the number of assigned entries
     *
     * @return int
     */
    public function countItemsAssigned()
    {
        return $this->row['assigned_items_count'];
    }

    /**
     * Return the processes current state
     *
     * @return string
     */
    public function getState()
    {
        if ($this->getActive() && $this->getProgress() < 100) {
            $stage = self::STATE_RUNNING;
        } elseif (!$this->getActive() && $this->getProgress() < 100) {
            $stage = self::STATE_CANCELLED;
        } else {
            $stage = self::STATE_COMPLETED;
        }
        return $stage;
    }

    /**
     * Returns the properties of the object as array
     *
     * @return array
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @param array $row
     *
     * @return void
     */
    public function setRow(array $row)
    {
        $this->row = $row;
    }
}
