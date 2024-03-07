<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use AOE\Crawler\Exception\ProcessException;
use AOE\Crawler\Helper\Sleeper\SleeperInterface;
use AOE\Crawler\Utility\PhpBinaryUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;

/**
 * @package AOE\Crawler\Service
 * @ignoreAnnotation("noRector")
 *
 * @internal since v9.2.5
 */
class ProcessService
{
    public function __construct(
        private readonly ProcessRepository $processRepository,
        private readonly SleeperInterface $sleeper
    ) {
    }

    /**
     * starts new process
     * @throws ProcessException if no crawler process was started
     */
    public function startProcess(int $timeToLive = 300): bool
    {
        $ttl = (time() + $timeToLive - 1);
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
            $this->sleeper->sleep(1);
        }
        throw new ProcessException('Something went wrong: process did not appear within 10 seconds.');
    }

    /**
     * Returns the path to start the crawler from the command line
     */
    public function getCrawlerCliPath(): string
    {
        $phpPath = PhpBinaryUtility::getPhpBinary();
        $typo3BinaryPath = $this->getComposerBinPath();

        $cliPart = 'typo3 crawler:processQueue';
        // Don't like the spacing, but don't have an better idea for now
        $scriptPath = $phpPath . ' ' . $typo3BinaryPath . $cliPart;

        if (Environment::isWindows()) {
            $scriptPath = str_replace('/', '\\', $scriptPath);
        }

        return ltrim($scriptPath);
    }

    private function getComposerBinPath(): ?string
    {
        // copied and modified from @see
        // https://github.com/TYPO3/typo3/blob/8a9c80b9d85ef986f5f369f1744fc26a6b607dda/typo3/sysext/scheduler/Classes/Controller/SchedulerModuleController.php#L402
        $composerJsonFile = getenv('TYPO3_PATH_COMPOSER_ROOT') . '/composer.json';
        if (!file_exists($composerJsonFile) || !($jsonContent = file_get_contents($composerJsonFile))) {
            return null;
        }
        $jsonConfig = @json_decode($jsonContent, true);
        if (empty($jsonConfig) || !is_array($jsonConfig)) {
            return null;
        }
        $vendorDir = trim($jsonConfig['config']['vendor-dir'] ?? 'vendor', '/');
        $binDir = trim($jsonConfig['config']['bin-dir'] ?? $vendorDir . '/bin', '/');

        return sprintf('%s/%s/', getenv('TYPO3_PATH_COMPOSER_ROOT'), $binDir);
    }
}
