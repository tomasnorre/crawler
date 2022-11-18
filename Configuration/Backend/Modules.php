<?php

declare(strict_types=1);

use AOE\Crawler\Backend\BackendModule;

/**
 * Definitions for modules provided by EXT:examples
 */
return [
    'web_info_crawler' => [
        'parent' => 'web_info',
        'access' => 'user',
        'path' => '/module/web/info/crawler',
        'iconIdentifier' => 'module-info',
        'labels' => [
            'title' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:moduleFunction.tx_crawler_modfunc1',
        ],
        'routes' => [
            '_default' => [
                'target' => BackendModule::class . '::main',
            ],
        ],
    ],
];
