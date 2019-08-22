<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Site Crawler',
    'description' => 'Libraries and scripts for crawling the TYPO3 page tree.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Kasper Skaarhoj, Daniel Poetzinger, Fabrizio Branca, Tolleiv Nietsch, Timo Schmidt, Michael Klapper, Stefan Rotsch, Tomas Norre Mikkelsen',
    'author_email' => 'dev@aoe.com',
    'author_company' => 'AOE GmbH',
    'version' => '9.0.0-dev',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.9-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
