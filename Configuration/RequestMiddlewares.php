<?php

declare(strict_types=1);

use TomasNorre\Crawler\Middleware\CrawlerInitialization;
use TomasNorre\Crawler\Middleware\FrontendUserAuthenticator;

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
