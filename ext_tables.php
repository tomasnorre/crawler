<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE=='BE')	{
    // add info module function
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        'tx_crawler_modfunc1',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modfunc1/class.tx_crawler_modfunc1.php',
        'LLL:EXT:crawler/locallang_db.php:moduleFunction.tx_crawler_modfunc1'
    );

    // add context menu item
    $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
        'name' => 'tx_crawler_contextMenu',
        'path' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'class.tx_crawler_contextMenu.php'
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_crawler_configuration');

if (version_compare(TYPO3_version, '4.5.0','<=')) {
	$TCA['tx_crawler_configuration']['columns']['sys_workspace_uid']['config']['items'][1] = Array('LLL:EXT:lang/locallang_misc.xml:shortcut_offlineWS',-1);
}
