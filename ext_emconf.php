<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Site Crawler',
    'description' => 'Libraries and scripts for crawling the TYPO3 page tree.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Tomas Norre Mikkelsen',
    'author_email' => 'tomasnorre@gmail.com',
    'author_company' => '',
    'version' => '11.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.11-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
