<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2023-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;

class BackendModuleHtmlElementService
{
    /**
     * Todo: Mode XLF Labels to Fluid Templates then the methods can be merged into one to some degree.
     * This should be done when it's added an 100% test coverage, after that i might even be possible to
     * switch to unit tests as the language service isn't needed anymore in the BackendModuleHtmLElementService
     */
    public function getItemsPerPageDropDownHtml(
        int $pageUid,
        int $itemsPerPage,
        array $menuItems,
        array $queryParameters
    ): string {
        return $this->getLanguageService()->sL(
            'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage'
        ) . ': ' .
            BackendUtility::getFuncMenu(
                $pageUid,
                'itemsPerPage',
                $itemsPerPage,
                $menuItems['itemsPerPage'],
                'index.php',
                $this->getAdditionalQueryParams('itemsPerPage', $queryParameters)
            );
    }

    public function getShowFeVarsCheckBoxHtml(
        int $pageUid,
        string $showFeVars,
        string $quiPath,
        array $queryParameters
    ): string {
        return BackendUtility::getFuncCheck(
            $pageUid,
            'ShowFeVars',
            $showFeVars,
            'index.php',
            $quiPath . $this->getAdditionalQueryParams('ShowFeVars', $queryParameters)
        ) . '&nbsp;' . $this->getLanguageService()->sL(
            'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showfevars'
        );
    }

    public function getShowResultLogCheckBoxHtml(
        int $pageUid,
        string $showResultLog,
        string $quiPath,
        array $queryParameters
    ): string {
        return BackendUtility::getFuncCheck(
            $pageUid,
            'ShowResultLog',
            $showResultLog,
            'index.php',
            $quiPath . $this->getAdditionalQueryParams('ShowResultLog', $queryParameters)
        ) . '&nbsp;' . $this->getLanguageService()->sL(
            'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showresultlog'
        );
    }

    public function getDisplayLogFilterHtml(
        int $pageUid,
        string $logDisplay,
        array $menuItems,
        array $queryParameters
    ): string {
        return $this->getLanguageService()->sL(
            'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.display'
        ) . ': ' . BackendUtility::getFuncMenu(
            $pageUid,
            'logDisplay',
            $logDisplay,
            $menuItems['log_display'],
            'index.php',
            $this->getAdditionalQueryParams('logDisplay', $queryParameters)
        );
    }

    public function getDepthDropDownHtml(
        int $id,
        string $currentValue,
        array $menuItems,
        array $queryParameters
    ): string {
        return BackendUtility::getFuncMenu(
            $id,
            'logDepth',
            $currentValue,
            $menuItems,
            'index.php',
            $this->getAdditionalQueryParams('logDepth', $queryParameters)
        );
    }

    /*
     * Build query string with affected checkbox/dropdown value removed.
     */
    private function getAdditionalQueryParams(string $keyToBeRemoved, array $queryParameters): string
    {
        $queryString = '';
        unset($queryParameters[$keyToBeRemoved]);
        foreach ($queryParameters as $key => $value) {
            $queryString .= "&{$key}={$value}";
        }
        return $queryString;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
