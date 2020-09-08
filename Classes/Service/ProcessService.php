<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Exception\ProcessException;
use AOE\Crawler\Utility\PhpBinaryUtility;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ProcessService
 *
 * @package AOE\Crawler\Service
 * @ignoreAnnotation("noRector")
 */
class ProcessService
{
    use PublicMethodDeprecationTrait;
    use PublicPropertyDeprecationTrait;

    /**
     * @var string[]
     * @noRector
     * @noRector \Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector
     * @noRector \Rector\DeadCode\Rector\Class_\RemoveSetterOnlyPropertyAndMethodCallRector
     */
    private $deprecatedPublicProperties = [
        'queueRepository' => 'Using queueRepository is deprecated since 9.0.1 and will be removed in v11.x',
        'crawlerController' => 'Using crawlerController is deprecated since 9.0.1 and will be removed in v11.x',
        'countInARun' => 'Using countInARun is deprecated since 9.0.1 and will be removed in v11.x',
        'processLimit' => 'Using processLimit is deprecated since 9.0.1 and will be removed in v11.x',
        'verbose' => 'Using verbose is deprecated since 9.0.1 and will be removed in v11.x',
    ];

    /**
     * @var string[]
     * @noRector
     * @noRector \Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector
     * @noRector \Rector\DeadCode\Rector\Class_\RemoveSetterOnlyPropertyAndMethodCallRector
     */
    private $deprecatedPublicMethods = [
        'multiProcess' => 'Using ProcessService::multiProcess() is deprecated since 9.0.1 and will be removed in v11.x',
        'reportItemStatus' => 'Using ProcessService::reportItemStatus() is deprecated since 9.0.1 and will be removed in v11.x',
        'startRequiredProcesses' => 'Using ProcessService::startRequiredProcesses() is deprecated since 9.0.1 and will be removed in v11.x',
    ];

    /**
     * @var int
     */
    private $timeToLive;

    /**
     * @var int
     * @deprecated
     */
    private $countInARun;

    /**
     * @var int
     * @deprecated
     */
    private $processLimit;

    /**
     * @var CrawlerController
     * @deprecated
     */
    private $crawlerController;

    /**
     * @var \AOE\Crawler\Domain\Repository\QueueRepository
     * @deprecated
     */
    private $queueRepository;

    /**
     * @var \AOE\Crawler\Domain\Repository\ProcessRepository
     */
    private $processRepository;

    /**
     * @var array
     */
    private $extensionSettings;

    /**
     * @var bool
     * @deprecated
     */
    private $verbose;

    /**
     * the constructor
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->processRepository = $objectManager->get(ProcessRepository::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
        $this->extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $this->timeToLive = (int) $this->extensionSettings['processMaxRunTime'];
        $this->countInARun = (int) $this->extensionSettings['countInARun'];
        $this->processLimit = (int) $this->extensionSettings['processLimit'];
        $this->verbose = (bool) $this->extensionSettings['processVerbose'];
    }

    /**
     * starts multiple processes
     *
     * @param integer $timeout
     *
     * @throws \RuntimeException
     * @deprecated
     */
    public function multiProcess($timeout): void
    {
        if ($this->processLimit <= 1) {
            throw new \RuntimeException('To run crawler in multi process mode you have to configure the processLimit > 1.' . PHP_EOL);
        }

        $pendingItemsStart = $this->queueRepository->countAllPendingItems();
        $itemReportLimit = 20;
        $reportItemCount = $pendingItemsStart - $itemReportLimit;
        if ($this->verbose) {
            $this->reportItemStatus();
        }
        $this->startRequiredProcesses();
        $nextTimeOut = time() + $this->timeToLive;
        $currentPendingItems = '';
        for ($i = 0; $i < $timeout; $i++) {
            $currentPendingItems = $this->queueRepository->countAllPendingItems();
            if ($this->startRequiredProcesses()) {
                $nextTimeOut = time() + $this->timeToLive;
            }
            if ($currentPendingItems === 0) {
                if ($this->verbose) {
                    echo 'Finished...' . chr(10);
                }
                break;
            }
            if ($currentPendingItems < $reportItemCount) {
                if ($this->verbose) {
                    $this->reportItemStatus();
                }
                $reportItemCount = $currentPendingItems - $itemReportLimit;
            }
            sleep(1);
            if ($nextTimeOut < time()) {
                $timedOutProcesses = $this->processRepository->findAll();
                $nextTimeOut = time() + $this->timeToLive;
                if ($this->verbose) {
                    echo 'Cleanup' . implode(',', $timedOutProcesses->getProcessIds()) . chr(10);
                }
                $this->crawlerController->CLI_releaseProcesses($timedOutProcesses->getProcessIds());
            }
        }
        if ($currentPendingItems > 0 && $this->verbose) {
            echo 'Stop with timeout' . chr(10);
        }
    }

    /**
     * starts new process
     * @throws ProcessException if no crawler process was started
     */
    public function startProcess(): bool
    {
        $ttl = (time() + $this->timeToLive - 1);
        $current = $this->processRepository->countNotTimeouted($ttl);

        // Check whether OS is Windows
        if (Environment::isWindows()) {
            $completePath = 'start ' . $this->getCrawlerCliPath();
        } else {
            $completePath = '(' . $this->getCrawlerCliPath() . ' &) > /dev/null';
        }

        $fileHandler = CommandUtility::exec($completePath);
        if ($fileHandler === false) {
            throw new ProcessException('could not start process!');
        }
        for ($i = 0; $i < 10; $i++) {
            if ($this->processRepository->countNotTimeouted($ttl) > $current) {
                return true;
            }
            sleep(1);
        }
        throw new ProcessException('Something went wrong: process did not appear within 10 seconds.');
    }

    /**
     * Returns the path to start the crawler from the command line
     */
    public function getCrawlerCliPath(): string
    {
        $phpPath = PhpBinaryUtility::getPhpBinary();
        $typo3BinaryPath = ExtensionManagementUtility::extPath('core') . 'bin/';
        $cliPart = 'typo3 crawler:processQueue';
        // Don't like the spacing, but don't have an better idea for now
        $scriptPath = $phpPath . ' ' . $typo3BinaryPath . $cliPart;

        if (Environment::isWindows()) {
            $scriptPath = str_replace('/', '\\', $scriptPath);
        }

        return ltrim($scriptPath);
    }

    /**
     * Reports curent Status of queue
     * @deprecated
     */
    protected function reportItemStatus(): void
    {
        echo 'Pending:' . $this->queueRepository->countAllPendingItems() . ' / Assigned:' . $this->queueRepository->countAllAssignedPendingItems() . chr(10);
    }

    /**
     * according to the given count of pending items and the countInARun Setting this method
     * starts more crawling processes
     *
     * @return boolean if processes are started
     * @throws ProcessException
     * @deprecated
     */
    private function startRequiredProcesses()
    {
        $ret = false;
        $currentProcesses = $this->processRepository->findAllActive()->count();
        $availableProcessesCount = $this->processLimit - $currentProcesses;
        $requiredProcessesCount = ceil($this->queueRepository->countAllUnassignedPendingItems() / $this->countInARun);
        $startProcessCount = min([$availableProcessesCount, $requiredProcessesCount]);
        if ($startProcessCount <= 0) {
            return $ret;
        }
        if ($startProcessCount && $this->verbose) {
            echo 'Start ' . $startProcessCount . ' new processes (Running:' . $currentProcesses . ')';
        }
        for ($i = 0; $i < $startProcessCount; $i++) {
            usleep(100);
            if ($this->startProcess()) {
                if ($this->verbose) {
                    echo '.';
                    $ret = true;
                }
            }
        }
        if ($this->verbose) {
            echo chr(10);
        }
        return $ret;
    }
}
