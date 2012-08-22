<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{

		// add info module function
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_crawler_modfunc1',
		t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_crawler_modfunc1.php',
		'LLL:EXT:crawler/locallang_db.php:moduleFunction.tx_crawler_modfunc1'
	);

		// add context menu item
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
		'name' => 'tx_crawler_contextMenu',
		'path' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_crawler_contextMenu.php'
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
        "fe_admin_fieldList" => "hidden, name, processing_instruction_filter, processing_instruction_parameters_ts, configuration, base_url, sys_domain_base_url, pidsonly, begroups,fegroups, sys_workspace_uid, realurl, chash, exclude",
    )
);

if (version_compare(TYPO3_version, '4.5.0','<=')) {
	t3lib_div::loadTCA("tx_crawler_configuration");
	$TCA['tx_crawler_configuration']['columns']['sys_workspace_uid']['config']['items'][1] = Array('LLL:EXT:lang/locallang_misc.xml:shortcut_offlineWS',-1);
}

?>