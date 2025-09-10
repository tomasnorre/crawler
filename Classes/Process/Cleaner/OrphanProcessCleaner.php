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
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OrphanProcessCleaner
{
    public function __construct(
        private readonly ProcessRepository $processRepository,
        private readonly QueueRepository $queueRepository,
        private readonly ProcessManagerInterface $processManager
    ) {
    }

    public function clean(): void
    {
        $results = $this->processRepository->getActiveOrphanProcesses();

        foreach ($results as $result) {
            $systemProcessId = (int) $result['system_process_id'];
            $processId = $result['process_id'];

            if ($systemProcessId <= 1) {
                continue;
            }

            $dispatcherProcesses = $this->processManager->findDispatcherProcesses();
            if (empty($dispatcherProcesses)) {
                $this->remove($processId);
                return;
            }

            $exists = false;
            foreach ($dispatcherProcesses as $process) {
                $parts = GeneralUtility::trimExplode(' ', $process, true);
                if ($systemProcessId === (int) ($parts[1] ?? 0)) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $this->remove($processId);
            }
        }
    }

    private function remove(string $processId): void
    {
        $this->processRepository->removeByProcessId($processId);
        $this->queueRepository->unsetQueueProcessId($processId);
    }
}
