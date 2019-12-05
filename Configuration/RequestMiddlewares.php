<?php

declare(strict_types=1);

use AOE\Crawler\Middleware\CrawlerInitialization;

return [
    'frontend' => [
        'aoe/crawler/initialization' => [
            'target' => CrawlerInitialization::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/authentication',
            ],
        ],
    ],
];
