<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Site Crawler',
    'description' => 'Libraries and scripts for crawling the TYPO3 page tree.',
    'category' => 'module',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Stefan Rotsch, Tomas Norre Mikkelsen',
    'author_email' => 'dev@aoe.com',
    'author_company' => 'AOE GmbH',
    'version' => '6.0.0-dev',
    'constraints' => array(
        'depends' => array(
            'php' => '7.0.0-7.99.99',
            'typo3' => '8.5.0-8.99.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    )
);