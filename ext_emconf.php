<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Site Crawler',
    'description' => 'TYPO3 Crawler crawls the TYPO3 page tree. Used for cache warmup, indexing, publishing applications etc.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Tomas Norre Mikkelsen',
    'author_email' => 'tomasnorre@gmail.com',
    'author_company' => '',
    'version' => '12.0.10',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.99.99',
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
