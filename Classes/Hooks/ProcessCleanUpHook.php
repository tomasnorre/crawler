<?php

declare(strict_types=1);

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

namespace AOE\Crawler\Hooks;

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * @internal since v9.2.5
 */
class ProcessCleanUpHook implements CrawlerHookInterface
{
    /**
     * @var ProcessRepository
     */
    protected $processRepository;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var CrawlerController
     */
    private $crawlerController;

    /**
     * @var array
     */
    private $extensionSettings;

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->processRepository = $objectManager->get(ProcessRepository::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
    }

    /**
     * Main function of process CleanUp Hook.
     *
     * @param CrawlerController $crawlerController Crawler Lib class
     */
    public function crawler_init(CrawlerController $crawlerController): void
    {
        $this->crawlerController = $crawlerController;
        $this->extensionSettings = $this->crawlerController->extensionSettings;

        // Clean Up
        $this->removeActiveOrphanProcesses();
        $this->removeActiveProcessesOlderThanOneHour();
    }

    /**
     * Remove active processes older than one hour
     */
    private function removeActiveProcessesOlderThanOneHour(): void
    {
        $results = $this->processRepository->getActiveProcessesOlderThanOneHour();

        if (! is_array($results)) {
            return;
        }
        foreach ($results as $result) {
            $systemProcessId = (int) $result['system_process_id'];
            $processId = $result['process_id'];
            if ($systemProcessId > 1) {
                if ($this->doProcessStillExists($systemProcessId)) {
                    $this->killProcess($systemProcessId);
                }
                $this->removeProcessFromProcesslist($processId);
            }
        }
    }

    /**
     * Removes active orphan processes from process list
     */
    private function removeActiveOrphanProcesses(): void
    {
        $results = $this->processRepository->getActiveOrphanProcesses();

        if (! is_array($results)) {
            return;
        }
        foreach ($results as $result) {
            $processExists = false;
            $systemProcessId = (int) $result['system_process_id'];
            $processId = $result['process_id'];
            if ($systemProcessId > 1) {
                $dispatcherProcesses = $this->findDispatcherProcesses();
                if (! is_array($dispatcherProcesses) || empty($dispatcherProcesses)) {
                    $this->removeProcessFromProcesslist($processId);
                    return;
                }
                foreach ($dispatcherProcesses as $process) {
                    $responseArray = $this->createResponseArray($process);
                    if ($systemProcessId === (int) $responseArray[1]) {
                        $processExists = true;
                    };
                }
                if (! $processExists) {
                    $this->removeProcessFromProcesslist($processId);
                }
            }
        }
    }

    /**
     * Remove a process from processlist
     *
     * @param string $processId Unique process Id.
     */
    private function removeProcessFromProcesslist($processId): void
    {
        $this->processRepository->removeByProcessId($processId);
        $this->queueRepository->unsetQueueProcessId($processId);
    }

    /**
     * Create response array
     * Convert string to array with space character as delimiter,
     * removes all empty records to have a cleaner array
     *
     * @param string $string String to create array from
     *
     * @return array
     */
    private function createResponseArray($string)
    {
        $responseArray = GeneralUtility::trimExplode(' ', $string, true);
        return array_values($responseArray);
    }

    /**
     * Check if the process still exists
     *
     * @param int $pid Process id to be checked.
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function doProcessStillExists($pid)
    {
        $doProcessStillExists = false;
        if (! Environment::isWindows()) {
            // Not windows
            if (file_exists('/proc/' . $pid)) {
                $doProcessStillExists = true;
            }
        } else {
            // Windows
            exec('tasklist | find "' . $pid . '"', $returnArray, $returnValue);
            if (count($returnArray) > 0 && stripos($returnArray[0], 'php') !== false) {
                $doProcessStillExists = true;
            }
        }
        return $doProcessStillExists;
    }

    /**
     * Kills a process
     *
     * @param int $pid Process id to kill
     *
     * @codeCoverageIgnore
     */
    private function killProcess($pid): void
    {
        if (! Environment::isWindows()) {
            // Not windows
            posix_kill($pid, 9);
        } else {
            // Windows
            exec('taskkill /PID ' . $pid);
        }
    }

    /**
     * Find dispatcher processes
     *
     * @return array
     * @codeCoverageIgnore
     */
    private function findDispatcherProcesses()
    {
        $returnArray = [];
        if (! Environment::isWindows()) {
            // Not windows
            exec('ps aux | grep \'typo3 crawler:processQueue\'', $returnArray, $returnValue);
        } else {
            // Windows
            exec('tasklist | find \'typo3 crawler:processQueue\'', $returnArray, $returnValue);
        }
        return $returnArray;
    }
}
