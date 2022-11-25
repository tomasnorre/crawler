<?php

declare(strict_types=1);

/*
 * (c) 2022-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Controller\BackendModuleController;

return [
    'web_site_crawler' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/page/crawler',
        'labels' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf',
        'extensionName' => 'Crawler',
        'iconIdentifier' => 'tx-crawler-icon',
        'routes' => [
            '_default' => [
                'target' => BackendModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
