<?php
$EM_CONF[$_EXTKEY] = array(
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
    'version' => '5.1.3',
    'constraints' => array(
        'depends' => array(
            'php' => '5.3.0-7.0.99',
            'typo3' => '6.2.0-7.6.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    )
);
