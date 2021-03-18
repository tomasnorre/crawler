<?php

declare(strict_types=1);

defined('TYPO3_MODE') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'tx-crawler',
        ],
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,name,force_ssl,processing_instruction_filter,processing_instruction_parameters_ts,configuration,base_url,pidsonly,begroups,exclude',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim,lower,alphanum_x',
            ],
        ],
        'force_ssl' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.crawling_protocol',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.crawling_protocol.http',
                        -1,
                    ],
                    [
                        'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.crawling_protocol.page_config',
                        0,
                    ],
                    [
                        'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.crawling_protocol.https',
                        1,
                    ],
                ],
                'default' => 0,
            ],
        ],
        'processing_instruction_filter' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.processing_instruction_filter',
            'description' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.processing_instruction_filter.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => AOE\Crawler\Utility\TcaUtility::class . '->getProcessingInstructions',
                'maxitems' => 100,
            ],
        ],
        'processing_instruction_parameters_ts' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.processing_instruction_parameters_ts',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 5,
            ],
        ],
        'configuration' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.configuration',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 5,
            ],
        ],
        'base_url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.base_url',
            'description' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.base_url.description',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'pidsonly' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.pidsonly',
            'description' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.pidsonly.description',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 100,
            ],
        ],
        'begroups' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.begroups',
            'description' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.begroups.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'foreign_table' => 'be_groups',
            ],
        ],
        'fegroups' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.fegroups',
            'description' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.fegroups.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
            ],
        ],
        'exclude' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.exclude',
            'description' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.exclude.description',
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'rows' => 3,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                name, force_ssl, processing_instruction_filter, base_url, pidsonly, exclude,
                configuration, processing_instruction_parameters_ts, fegroups,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden, begroups,
            ',
        ],
    ],
];
