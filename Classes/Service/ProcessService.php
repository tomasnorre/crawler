<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Exception\ProcessException;
use AOE\Crawler\Utility\PhpBinaryUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * @package AOE\Crawler\Service
 * @ignoreAnnotation("noRector")
 *
 * @internal since v9.2.5
 */
class ProcessService
{
    /**
     * @var int
     */
    private $timeToLive;

    /**
     * @var \AOE\Crawler\Domain\Repository\ProcessRepository
     */
    private $processRepository;

    /**
     * @var array
     */
    private $extensionSettings;

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->processRepository = $objectManager->get(ProcessRepository::class);
        $this->extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $this->timeToLive = (int) $this->extensionSettings['processMaxRunTime'];
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

        $output = null;
        $returnValue = 0;
        CommandUtility::exec($completePath, $output, $returnValue);
        if ($returnValue !== 0) {
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

    public function setProcessRepository(ProcessRepository $processRepository): void
    {
        $this->processRepository = $processRepository;
    }
}
