<?php

declare(strict_types=1);

$EM_CONF['typo3_console'] = [
    'title' => 'TYPO3 Console',
    'description' => 'A reliable and powerful command line interface for TYPO3 CMS',
    'category' => 'cli',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Helmut Hummel',
    'author_email' => 'info@helhum.io',
    'author_company' => 'helhum.io',
    'version' => '5.7.2',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.3.99',
            'typo3' => '8.7.22-9.5.99',
            'extbase' => '8.7.22-9.5.99',
            'extensionmanager' => '8.7.22-9.5.99',
            'fluid' => '8.7.22-9.5.99',
            'install' => '8.7.22-9.5.99',
            'scheduler' => '8.7.22-9.5.99',
        ],
        'conflicts' => [
            'dbal' => '',
        ],
        'suggests' => [],
    ],
];
