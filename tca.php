<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA["tx_crawler_configuration"] = array (
    "ctrl" => $TCA["tx_crawler_configuration"]["ctrl"],
    "interface" => array (
        "showRecordFieldList" => "hidden,name,processing_instruction_filter,processing_instruction_parameters_ts,configuration,base_url,pidsonly,begroups"
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
                "type" => "select",
                "itemsProcFunc" => "EXT:crawler/class.tx_crawler_tcaFunc.php:tx_crawler_tcaFunc->getProcessingInstructions",
        		"eval" => "required",
        		"renderMode" => "checkbox",
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
		'begroups' => Array (
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.begroups',
			'config' => Array (
				'type' => 'select',
				'size' => 5,
				'maxitems' => 20,
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'be_groups'
			)
		),
    ),
    "types" => array (
        "0" => array("showitem" => "name, processing_instruction_filter, configuration, base_url, pidsonly, processing_instruction_parameters_ts,begroups")
    ),
    "palettes" => array (
        "1" => array("showitem" => "")
    )
);
?>