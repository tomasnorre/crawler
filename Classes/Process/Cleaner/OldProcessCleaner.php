<?php

declare(strict_types=1);

namespace AOE\Crawler\Process\Cleaner;

/*
 * (c) 2025 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Process\ProcessManagerInterface;

/**
 * @internal since v12.0.10
 */
class OldProcessCleaner
{
    public function __construct(
        private readonly ProcessRepository $processRepository,
        private readonly QueueRepository $queueRepository,
        private readonly ProcessManagerInterface $processManager
    ) {
    }

    public function clean(): void
    {
        $results = $this->processRepository->getActiveProcessesOlderThanOneHour();

        if (!is_array($results)) {
            throw new \UnexpectedValueException('Expected array, got ' . gettype($results));
        }

        foreach ($results as $result) {
            $systemProcessId = (int) $result['system_process_id'];
            $processId = $result['process_id'];

            if ($systemProcessId <= 1) {
                continue;
            }

            if ($this->processManager->processExists($systemProcessId)) {
                $this->processManager->killProcess($systemProcessId);
            }

            $this->processRepository->removeByProcessId($processId);
            $this->queueRepository->unsetQueueProcessId($processId);
        }
    }
}
