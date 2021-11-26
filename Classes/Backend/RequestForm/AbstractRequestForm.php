<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend\RequestForm;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Utility\PhpBinaryUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AbstractRequestForm
{
    protected CrawlerController $crawlerController;
    protected bool $isErrorDetected = false;
    protected array $extensionSettings = [];

    protected function findCrawler(): CrawlerController
    {
        if (! $this->crawlerController instanceof CrawlerController) {
            $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
        }
        return $this->crawlerController;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Verify that the crawler is executable.
     */
    protected function makeCrawlerProcessableChecks(array $extensionSettings): void
    {
        if (! $this->isPhpForkAvailable()) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:message.noPhpForkAvailable'));
        }

        $exitCode = 0;
        $out = [];
        CommandUtility::exec(
            PhpBinaryUtility::getPhpBinary() . ' -v',
            $out,
            $exitCode
        );
        if ($exitCode > 0) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:message.phpBinaryNotFound'), htmlspecialchars($extensionSettings['phpPath'], ENT_QUOTES | ENT_HTML5)));
        }
    }

    /**
     * Indicate that the required PHP method "popen" is
     * available in the system.
     */
    private function isPhpForkAvailable(): bool
    {
        return function_exists('popen');
    }
}
