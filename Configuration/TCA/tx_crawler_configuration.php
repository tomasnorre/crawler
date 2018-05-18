<?php
defined('TYPO3_MODE') or die();

$_EXTKEY = 'crawler';

$GLOBALS['TCA']['tx_crawler_configuration'] = [
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
        'iconfile' => 'EXT:crawler/Resources/Public/Icons/icon_tx_crawler_configuration.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'hidden, name, force_ssl, processing_instruction_filter, processing_instruction_parameters_ts, configuration, base_url, sys_domain_base_url, pidsonly, begroups,fegroups, realurl, chash, exclude',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,name,force_ssl,processing_instruction_filter,processing_instruction_parameters_ts,configuration,base_url,pidsonly,begroups,realurl,chash, exclude'
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.name',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required,trim,lower,alphanum_x',
            ]
        ],
        'force_ssl' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.crawling_protocol',
            'config' => [
                'type' => 'select',
                'maxitems' => 1,
                'items' => array(
                    array('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.crawling_protocol.http', -1),
                    array('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.crawling_protocol.page_config', 0),
                    array('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.crawling_protocol.https', 1),
                )
            ]
        ],
        'processing_instruction_filter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.processing_instruction_filter',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => 'AOE\\Crawler\\Utility\\TcaUtility->getProcessingInstructions',
                'eval' => 'required',
                'maxitems' => 100
            ]
        ],
        'processing_instruction_parameters_ts' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.processing_instruction_parameters_ts',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '5',
            ],
            'defaultExtras' => 'fixed-font : enable-tab',
        ],
        'configuration' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.configuration',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '5',
            ]
        ],
        'base_url' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.base_url',
            'displayCond' => 'FIELD:sys_domain_base_url:REQ:false',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'sys_domain_base_url' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.sys_domain_base_url',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'sys_domain',
                'foreign_table_where' => 'ORDER BY pages.uid',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ]
        ],
        'pidsonly' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.pidsonly',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 100,
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest',
                    ],
                ],
            ],
        ],
        'begroups' => [
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.begroups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'be_groups',
            ],
        ],
        'fegroups' => [
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.fegroups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups'
            ]
        ],
        'realurl' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.realurl',
            'config' => [
                'type' => 'check',
            ]
        ],
        'chash' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.chash',
            'config' => [
                'type' => 'check',
            ]
        ],
        'exclude' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.exclude',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '3',
            ]
        ],
        'root_template_pid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.root_template_pid',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => '1',
                "eval" => "required",
                'maxitems' => '1',
                'minitems' => '0',
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ],
                'default' => 0
            ]
        ]
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, name, force_ssl, processing_instruction_filter, base_url, sys_domain_base_url, root_template_pid, pidsonly, configuration, processing_instruction_parameters_ts,begroups, fegroups, realurl, chash, exclude']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
