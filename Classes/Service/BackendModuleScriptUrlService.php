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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendModuleScriptUrlService
{
    /**
     * @param array<array-key, mixed> $queryParameters
     */
    public function buildScriptUrl(
        ServerRequestInterface $request,
        string $elementName,
        int $pageUid,
        array $queryParameters,
        string $queryString = ''
    ): string {
        $mainParams = [
            'id' => $pageUid,
        ];
        $route = $request->getAttribute('route');
        if (!$route instanceof Route) {
            throw new \RuntimeException('The request has no backend route to build the script URL from', 1763290800);
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $scriptUrl = $uriBuilder->buildUriFromRoute($route->getOption('_identifier'), $mainParams);

        return $scriptUrl . ($queryString . $this->getAdditionalQueryParams(
            $elementName,
            $queryParameters
        ) . '&' . $elementName . '=${value}');
    }

    /**
     * Build query string with affected checkbox/dropdown value removed.
     *
     * @param array<array-key, mixed> $queryParameters
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
