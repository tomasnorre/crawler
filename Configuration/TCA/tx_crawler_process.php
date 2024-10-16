<?php

declare(strict_types=1);

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_processs',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'tx-crawler',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'process_id' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,lower,alphanum_x',
                'required' => true,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,name,force_ssl,processing_instruction_filter,base_url,pidsonly,exclude,configuration,processing_instruction_parameters_ts,fegroups,--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,hidden,begroups',
        ],
    ],
];
