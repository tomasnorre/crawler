<?php

declare(strict_types=1);

use AOE\Crawler\Backend\BackendModule;

/**
 * Definitions for modules provided by EXT:examples
 */
return [
    'web_site_crawler' => [
        'parent' => 'web',
        'access' => 'user',
        'position' => ['after' => 'web_info'],
        'workspaces' => 'live',
        'path' => '/module/page/crawler',
        'extensionName' => 'Crawler',
        'iconIdentifier' => 'tx-crawler-icon',
        'labels' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf',
        'routes' => [
            '_default' => [
                'target' => BackendModule::class . '::main',
            ],
        ],
    ],


];
