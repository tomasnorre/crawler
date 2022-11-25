<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx-crawler' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:crawler/Resources/Public/Icons/crawler_configuration.svg',
    ],
    'tx-crawler-start' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:crawler/Resources/Public/Icons/crawler_start.svg',
    ],
    'tx-crawler-stop' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:crawler/Resources/Public/Icons/crawler_stop.svg',
    ],
    'tx-crawler-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:crawler/Resources/Public/Icons/Extension.svg',
    ]
];
