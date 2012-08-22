<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA["tx_crawler_configuration"] = array (
    "ctrl" => $TCA["tx_crawler_configuration"]["ctrl"],
    "interface" => array (
        "showRecordFieldList" => "hidden,name,processing_instruction_filter,processing_instruction_parameters_ts,configuration,base_url,pidsonly,begroups,realurl,chash, exclude"
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
       		"eval" => "required,trim,lower,alphanum_x",
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
                "type" => "text",
                "cols" => "40",
                "rows" => "5",
            )
        ),
        "base_url" => Array (
            "exclude" => 1,
            "label" => "LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.base_url",
			'displayCond' => 'FIELD:sys_domain_base_url:=:0',
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
				'size' => 5,
				'maxitems' => 20,
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'fe_groups'
			)
		),
		'sys_workspace_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.sys_workspace_uid',
			'displayCond' => (version_compare(TYPO3_version,'4.5.0','>=') ? 'EXT:workspaces:LOADED:true' : 'EXT:version:LOADED:true'),
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_misc.xml:shortcut_onlineWS',0),
				),
				'size' => 1,
				'maxitems' => 1,
				'exclusiveKeys' => '-1,0',
				'foreign_table' => 'sys_workspace'
			)
		),
		'realurl' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_configuration.realurl',
			'displayCond' => 'FALSE',
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
        "0" => array("showitem" => "name, processing_instruction_filter, configuration, base_url, sys_domain_base_url, pidsonly, processing_instruction_parameters_ts,begroups, fegroups, sys_workspace_uid, realurl, chash, exclude")
    ),
    "palettes" => array (
        "1" => array("showitem" => "")
    )
);

?>
