<?php

declare(strict_types=1);

use AOE\Crawler\Middleware\ContentFinisher;
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
        'aoe/crawler/content-finisher' => [
            'target' => ContentFinisher::class,
            'before' => [
                'typo3/cms-frontend/timetracker',
            ],
        ],
    ],
];
