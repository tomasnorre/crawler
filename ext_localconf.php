<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler'] 			= array('EXT:crawler/cli/crawler_cli.php','_CLI_crawler');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_im'] 		= array('EXT:crawler/cli/crawler_im.php','_CLI_crawler');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_flush'] 	= array('EXT:crawler/cli/crawler_flush.php','_CLI_crawler');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_multiprocess'] 	= array('EXT:crawler/cli/crawler_multiprocess.php','_CLI_crawler');

\AOE\Crawler\Utility\HookUtility::registerHooks($_EXTKEY);
\AOE\Crawler\Utility\SchedulerUtility::registerSchedulerTasks($_EXTKEY);
\AOE\Crawler\Utility\CacheUtility::registerRootLineCache();

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = true;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
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

		'className' => 'tx_crawler_auth',
	)
);
