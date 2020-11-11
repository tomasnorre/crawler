<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend\Helper;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UrlBuilder
{
    /**
     * Returns the URL to the current module, including $_GET['id'].
     *
     * @param array $uriParameters optional parameters to add to the URL
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public static function getInfoModuleUrl(array $uriParameters = []): Uri
    {
        if (GeneralUtility::_GP('id')) {
            $uriParameters['id'] = GeneralUtility::_GP('id');
        }
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return $uriBuilder->buildUriFromRoute('web_info', $uriParameters);
    }
}
