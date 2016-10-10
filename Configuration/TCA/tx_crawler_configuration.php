<?php
if (!defined ('TYPO3_MODE')) die('Access denied.');

$_EXTKEY = 'crawler';

$GLOBALS['TCA']['tx_crawler_configuration'] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => "ORDER BY crdate",
        'delete' => 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:crawler/icon_tx_crawler_configuration.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden, name, processing_instruction_filter, processing_instruction_parameters_ts, configuration, base_url, sys_domain_base_url, pidsonly, begroups,fegroups, realurl, chash, exclude",
    ),
    "interface" => array (
        "showRecordFieldList" => "hidden,name,processing_instruction_filter,processing_instruction_parameters_ts,configuration,base_url,pidsonly,begroups,realurl,chash, exclude"
    ),
    "columns" => array (
        'hidden' => array (
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
        "name" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.name",
            "config" => Array (
                "type" => "input",
                "size" => "30",
                "eval" => "required,trim,lower,alphanum_x",
            )
        ),
        "processing_instruction_filter" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.processing_instruction_filter",
            "config" => Array (
                "type" => "select",
                'renderType' => 'selectCheckBox',
                "itemsProcFunc" => "EXT:crawler/class.tx_crawler_tcaFunc.php:tx_crawler_tcaFunc->getProcessingInstructions",
                "eval" => "required",
                "maxitems" => 100
            )
        ),
        "processing_instruction_parameters_ts" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.processing_instruction_parameters_ts",
            "config" => Array (
                "type" => "text",
                "cols" => "40",
                "rows" => "5",
            ),
            "defaultExtras" => "fixed-font : enable-tab",
        ),
        "configuration" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.configuration",
            "config" => Array (
                "type" => "text",
                "cols" => "40",
                "rows" => "5",
            )
        ),
        "base_url" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.base_url",
            'displayCond' => 'FIELD:sys_domain_base_url:REQ:false',
            "config" => Array (
                "type" => "input",
                "size" => "30",
            )
        ),
        'sys_domain_base_url' => array (
            'exclude' => 0,
            'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.sys_domain_base_url',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array (
                    array('',0),
                ),
                'foreign_table' => 'sys_domain',
                'foreign_table_where' => 'ORDER BY pages.uid',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
        "pidsonly" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.pidsonly",
            "config" => Array (
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "pages",
                "size" => 5,
                "minitems" => 0,
                "maxitems" => 100,
                "wizards" => array (
                    "suggest" => array (
                        "type" => "suggest",
                    ),
                ),
            ),
        ),
        'begroups' => Array (
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.begroups',
            'config' => Array (
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'be_groups',
            ),
        ),
        'fegroups' => Array (
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.fegroups',
            'config' => Array (
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups'
            )
        ),
        'realurl' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.realurl',
            'config' => Array (
                'type' => 'check',
            )
        ),
        'chash' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.chash',
            'config' => Array (
                'type' => 'check',
            )
        ),
        'exclude' => Array (
            'exclude' => 1,
            'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.exclude',
            'config' => Array (
                'type' => 'text',
                'cols' => '48',
                'rows' => '3',
            )
        ),
    ),
    "types" => array (
        "0" => array("showitem" => "hidden, name, processing_instruction_filter, configuration, base_url, sys_domain_base_url, pidsonly, processing_instruction_parameters_ts,begroups, fegroups, realurl, chash, exclude")
    ),
    "palettes" => array (
        "1" => array("showitem" => "")
    )
);
