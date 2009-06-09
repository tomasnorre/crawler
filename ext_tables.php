<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_crawler_modfunc1',
		t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_crawler_modfunc1.php',
		'LLL:EXT:crawler/locallang_db.php:moduleFunction.tx_crawler_modfunc1'
	);
}

t3lib_extMgm::allowTableOnStandardPages('tx_crawler_configuration');

$TCA["tx_crawler_configuration"] = array (
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
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_crawler_configuration.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden, name, processing_instruction_filter, processing_instruction_parameters_ts, configuration, base_url, pidsonly, begroups",
    )
);

?>