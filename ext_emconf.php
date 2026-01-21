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
    'version' => '13.0.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.99.99',
            'typo3' => '13.4.0-14.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
