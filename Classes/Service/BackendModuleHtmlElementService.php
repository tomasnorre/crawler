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

class BackendModuleHtmlElementService
{
    public function getFormElementSelect(
        string $elementName,
        int $pageUid,
        string|int $currentValue,
        array $menuItems,
        array $queryParameters,
        string $queryString = ''
    ): string {
        return BackendUtility::getFuncMenu(
            $pageUid,
            $elementName,
            $currentValue,
            $menuItems[$elementName],
            'index.php',
            $queryString . $this->getAdditionalQueryParams($elementName, $queryParameters)
        );
    }

    public function getFormElementCheckbox(
        string $elementName,
        int $pageUid,
        string $currentValue,
        array $queryParameters,
        string $queryString = '',

    ) {
        return BackendUtility::getFuncCheck(
            $pageUid,
            $elementName,
            $currentValue,
            'index.php',
            $queryString . $this->getAdditionalQueryParams($elementName, $queryParameters)
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
}
