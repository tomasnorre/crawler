<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_crawler_modfunc1',
		t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_crawler_modfunc1.php',
		'LLL:EXT:crawler/locallang_db.xml:moduleFunction.tx_crawler_modfunc1'
	);
}

$TCA["tx_crawler_infopot"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:crawler/locallang_db.xml:tx_crawler_infopot',
        'label'     => 'uid',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'type' => 'type',
        'default_sortby' => "ORDER BY crdate",
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_crawler_infopot.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "type, queueentry_id",
    )
);
?>