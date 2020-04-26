<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class Process
 *
 * @package AOE\Crawler\Domain\Model
 */
class Process extends AbstractEntity
{
    public const STATE_RUNNING = 'running';
    public const STATE_CANCELLED = 'cancelled';
    public const STATE_COMPLETED = 'completed';

    /**
     * @var array
     */
    protected $row;

    /**
     * @var string
     */
    protected $processId = '';

    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @var int
     */
    protected $ttl = 0;

    /**
     * @var int
     */
    protected $assignedItemsCount = 0;

    /**
     * @var bool
     */
    protected $deleted = false;

    /**
     * @var string
     */
    protected $systemProcessId = '';

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
    }

    /**
     * @return string
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @param string $processId
     */
    public function setProcessId($processId): void
    {
        $this->processId = $processId;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active): void
    {
        $this->active = $active;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * @return int
     */
    public function getAssignedItemsCount()
    {
        return $this->assignedItemsCount;
    }

    /**
     * @param int $assignedItemsCount
     */
    public function setAssignedItemsCount($assignedItemsCount): void
    {
        $this->assignedItemsCount = $assignedItemsCount;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted($deleted): void
    {
        $this->deleted = $deleted;
    }

    /**
     * @return string
     */
    public function getSystemProcessId()
    {
        return $this->systemProcessId;
    }

    /**
     * @param string $systemProcessId
     */
    public function setSystemProcessId($systemProcessId): void
    {
        $this->systemProcessId = $systemProcessId;
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

    public function getTimeForLastItem(): int
    {
        $entry = $this->queueRepository->findOldestEntryForProcess($this);
        return $entry['exec_time'];
    }

    public function getTimeForFirstItem(): int
    {
        $entry = $this->queueRepository->findYoungestEntryForProcess($this);
        return $entry['exec_time'];
    }

    /**
     * Counts the number of items which need to be processed
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getAmountOfItemsProcessed()
    {
        return $this->queueRepository->countExecutedItemsByProcess($this);
    }

    /**
     * Counts the number of items which still need to be processed
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getItemsToProcess()
    {
        return $this->queueRepository->countNonExecutedItemsByProcess($this);
    }

    public function getFinallyAssigned(): int
    {
        return $this->getItemsToProcess() + $this->getAmountOfItemsProcessed();
    }

    /**
     * Returns the Progress of a crawling process as a percentage value
     *
     * @return float
     */
    public function getProgress()
    {
        $all = $this->getAssignedItemsCount();
        if ($all <= 0) {
            return 0;
        }

        $res = round((100 / $all) * $this->getAmountOfItemsProcessed());

        if ($res > 100.0) {
            return 100.0;
        }
        return $res;
    }

    /**
     * Return the processes current state
     *
     * @return string
     */
    public function getState()
    {
        if ($this->isActive() && $this->getProgress() < 100) {
            $stage = self::STATE_RUNNING;
        } elseif (!$this->isActive() && $this->getProgress() < 100) {
            $stage = self::STATE_CANCELLED;
        } else {
            $stage = self::STATE_COMPLETED;
        }
        return $stage;
    }
}
