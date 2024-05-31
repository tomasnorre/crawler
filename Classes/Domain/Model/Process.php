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

/**
 * Class Process
 *
 * @ignoreAnnotation("noRector")
 * @internal since v9.2.5
 */
class Process extends AbstractEntity
{
    final public const STATE_RUNNING = 'running';
    final public const STATE_CANCELLED = 'cancelled';
    final public const STATE_COMPLETED = 'completed';

    protected string $processId = '';

    protected bool $active = false;
    protected int $ttl = 0;
    protected int $assignedItemsCount = 0;

    protected bool $deleted = false;

    protected string $systemProcessId = '';
    protected QueueRepository$queueRepository;

    public function __construct()
    {
        $this->queueRepository = GeneralUtility::makeInstance(QueueRepository::class);
    }

    public function getProcessId(): string
    {
        return $this->processId;
    }

    public function setProcessId(string $processId): void
    {
        $this->processId = $processId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getAssignedItemsCount(): int
    {
        return $this->assignedItemsCount;
    }

    public function setAssignedItemsCount(int $assignedItemsCount): void
    {
        $this->assignedItemsCount = $assignedItemsCount;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getSystemProcessId(): string
    {
        return $this->systemProcessId;
    }

    public function setSystemProcessId(string $systemProcessId): void
    {
        $this->systemProcessId = $systemProcessId;
    }

    /**
     * Returns the difference between first and last processed item
     */
    public function getRuntime(): int
    {
        $lastItem = $this->getQueueRepository()->findOldestEntryForProcess($this);
        $firstItem = $this->getQueueRepository()->findYoungestEntryForProcess($this);
        $startTime = $firstItem['exec_time'] ?? 0;
        $endTime = $lastItem['exec_time'] ?? 0;
        return $endTime - $startTime;
    }

    /**
     * Counts the number of items which need to be processed
     * @codeCoverageIgnore
     */
    public function getAmountOfItemsProcessed(): int
    {
        return $this->getQueueRepository()->countExecutedItemsByProcess($this);
    }

    /**
     * Counts the number of items which still need to be processed
     * @codeCoverageIgnore
     */
    public function getItemsToProcess(): int
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
     */
    public function getState(): string
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

    private function getQueueRepository(): QueueRepository
    {
        $this->queueRepository ??= GeneralUtility::makeInstance(QueueRepository::class);
        return $this->queueRepository;
    }
}
