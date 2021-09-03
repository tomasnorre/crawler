<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Site Crawler',
    'description' => 'Libraries and scripts for crawling the TYPO3 page tree.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Kasper Skaarhoj, Daniel Poetzinger, Fabrizio Branca, Tolleiv Nietsch, Timo Schmidt, Michael Klapper, Stefan Rotsch, Tomas Norre Mikkelsen, Tizian Schmidlin',
    'author_email' => 'dev@aoe.com',
    'author_company' => 'AOE GmbH',
    'version' => '10.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.14-11.0.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
