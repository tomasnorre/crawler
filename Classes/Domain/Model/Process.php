<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Model;

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

use AOE\Crawler\Domain\Repository\QueueRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class Process
 *
 * @ignoreAnnotation("noRector")
 * @internal since v9.2.5
 */
class Process extends AbstractEntity
{
    public const STATE_RUNNING = 'running';

    public const STATE_CANCELLED = 'cancelled';

    public const STATE_COMPLETED = 'completed';

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
        $lastItem = $this->getQueueRepository()->findOldestEntryForProcess($this);
        $firstItem = $this->getQueueRepository()->findYoungestEntryForProcess($this);
        $startTime = $firstItem['exec_time'] ?? 0;
        $endTime = $lastItem['exec_time'] ?? 0;
        return $endTime - $startTime;
    }

    /**
     * Counts the number of items which need to be processed
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getAmountOfItemsProcessed()
    {
        return $this->getQueueRepository()->countExecutedItemsByProcess($this);
    }

    /**
     * Counts the number of items which still need to be processed
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getItemsToProcess()
    {
        return $this->getQueueRepository()->countNonExecutedItemsByProcess($this);
    }

    /**
     * @codeCoverageIgnore as it's a simple addition function
     */
    public function getFinallyAssigned(): int
    {
        return $this->getItemsToProcess() + $this->getAmountOfItemsProcessed();
    }

    /**
     * Returns the Progress of a crawling process as a percentage value
     */
    public function getProgress(): float
    {
        $all = $this->getAssignedItemsCount();
        if ($all <= 0) {
            return 0.0;
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
        } elseif (! $this->isActive() && $this->getProgress() < 100) {
            $stage = self::STATE_CANCELLED;
        } else {
            $stage = self::STATE_COMPLETED;
        }
        return $stage;
    }

    private function getQueueRepository(): QueueRepository
    {
        $this->queueRepository = $this->queueRepository ?? GeneralUtility::makeInstance(QueueRepository::class);
        return $this->queueRepository;
    }
}
