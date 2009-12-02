<?php

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler'] 			= array('EXT:crawler/cli/crawler_cli.php','_CLI_lowlevel');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_im'] 		= array('EXT:crawler/cli/crawler_im.php','_CLI_lowlevel');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_flush'] 	= array('EXT:crawler/cli/crawler_flush.php','_CLI_lowlevel');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']['tx_crawler'] = 'EXT:crawler/hooks/class.tx_crawler_hooks_tsfe.php:&tx_crawler_hooks_tsfe->fe_init';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser']['tx_crawler'] = 'EXT:crawler/hooks/class.tx_crawler_hooks_tsfe.php:&tx_crawler_hooks_tsfe->fe_feuserInit';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['tx_crawler'] = 'EXT:crawler/hooks/class.tx_crawler_hooks_tsfe.php:&tx_crawler_hooks_tsfe->fe_isOutputting';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['tx_crawler'] = 'EXT:crawler/hooks/class.tx_crawler_hooks_tsfe.php:&tx_crawler_hooks_tsfe->fe_eofe';

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = true;

t3lib_extMgm::addService(
	$_EXTKEY,
	'auth' /* sv type */,
	'tx_crawler_auth' /* sv key */,
	array(

		'title' => 'Login for wsPreview',
		'description' => '',

		'subtype' => 'getUserBE,authUserBE',

		'available' => TRUE,
		'priority' => 80,
		'quality' => 50,

		'os' => '',
		'exec' => '',

		'classFile' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_crawler_auth.php',
		'className' => 'tx_crawler_auth',
	)
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_crawler_scheduler_task'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_task.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_task.description',
	'additionalFields' => 'tx_crawler_scheduler_AdditionalFieldProvider'
);

?>