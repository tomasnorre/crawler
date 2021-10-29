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

use AOE\Crawler\Middleware\CrawlerInitialization;
use AOE\Crawler\Middleware\FrontendUserAuthenticator;

return [
    'frontend' => [
        'aoe/crawler/authentication' => [
            'target' => FrontendUserAuthenticator::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
        ],
        'aoe/crawler/initialization' => [
            'target' => CrawlerInitialization::class,
            'before' => [
                'typo3/cms-core/normalizedParams',
            ],
        ],
    ],
];
