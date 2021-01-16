<?php

return [
    'BE' => [
        'debug' => true,
        'defaultUC' => [
            'startModule' => 'help_AboutAbout'
        ]
    ],
    'DB' => [
        'Connections' => [
            'Default' => [
                'charset' => 'utf8mb4',
                'dbname' => 'crawler_gh',
                'driver' => 'mysqli',
                'host' => 'localhost',
                'password' => 'root',
                'port' => 3306,
                'tableoptions' => [
                    'charset' => 'utf8mb4',
                    'collate' => 'utf8mb4_unicode_ci',
                ],
                'user' => 'root',
            ],
        ],
    ],
    'EXTENSIONS' => [
        'backend' => [
            'backendFavicon' => '',
            'backendLogo' => '',
            'loginBackgroundImage' => '',
            'loginFootnote' => '',
            'loginHighlightColor' => '',
            'loginLogo' => '',
        ],
        'extensionmanager' => [
            'automaticInstallation' => '1',
            'offlineMode' => '0',
        ],
    ],
    'SYS' => [
        'encryptionKey' => 'ce65db146ca7894aa19d832a8435ae2cc7db13259d7424c3efeb428e2eae6566d97712bb79a9272c6b32569882356c22',
        'sitename' => 'container-tests',
        'trustedHostsPattern' => '.*.*'
    ],
];
