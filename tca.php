<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA["tx_crawler_configuration"] = array (
    "ctrl" => $TCA["tx_crawler_configuration"]["ctrl"],
    "interface" => array (
        "showRecordFieldList" => "hidden,name,processing_instruction_filter,processing_instruction_parameters_ts,configuration,base_url,pidsonly"
    ),
    "feInterface" => $TCA["tx_crawler_configuration"]["feInterface"],
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
        		"eval" => "required",
            )
        ),
        "processing_instruction_filter" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.processing_instruction_filter",
            "config" => Array (
                "type" => "input",
                "size" => "30",
            )
        ),
        "processing_instruction_parameters_ts" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.processing_instruction_parameters_ts",
            "config" => Array (
                "type" => "text",
                "cols" => "30",
                "rows" => "5",
            )
        ),
        "configuration" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.configuration",
            "config" => Array (
                "type" => "input",
                "size" => "30",
            )
        ),
        "base_url" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.base_url",
            "config" => Array (
                "type" => "input",
                "size" => "30",
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
            )
        ),
    ),
    "types" => array (
        "0" => array("showitem" => "hidden;;1;;1-1-1, name, processing_instruction_filter, processing_instruction_parameters_ts, configuration, base_url, pidsonly")
    ),
    "palettes" => array (
        "1" => array("showitem" => "")
    )
);
?>