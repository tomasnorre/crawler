<?php

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler'] 			= array('EXT:crawler/cli/crawler_cli.php','_CLI_crawler');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_im'] 		= array('EXT:crawler/cli/crawler_im.php','_CLI_crawler');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_flush'] 	= array('EXT:crawler/cli/crawler_flush.php','_CLI_crawler');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_multiprocess'] 	= array('EXT:crawler/cli/crawler_multiprocess.php','_CLI_crawler');


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']['tx_crawler'] = 'EXT:crawler/hooks/class.tx_crawler_hooks_tsfe.php:&tx_crawler_hooks_tsfe->fe_init';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser']['tx_crawler'] = 'EXT:crawler/hooks/class.tx_crawler_hooks_tsfe.php:&tx_crawler_hooks_tsfe->fe_feuserInit';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['tx_crawler'] = 'EXT:crawler/hooks/class.tx_crawler_hooks_tsfe.php:&tx_crawler_hooks_tsfe->fe_isOutputting';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['tx_crawler'] = 'EXT:crawler/hooks/class.tx_crawler_hooks_tsfe.php:&tx_crawler_hooks_tsfe->fe_eofe';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nc_staticfilecache/class.tx_ncstaticfilecache.php']['createFile_initializeVariables']['tx_crawler'] = 'EXT:' . $_EXTKEY . '/hooks/class.tx_crawler_hooks_staticFileCacheCreateUri.php:tx_crawler_hooks_staticFileCacheCreateUri->initialize';

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = true;

// Activating cli_hooks
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['cli_hooks'][] = 'tx_crawler_hooks_processCleanUp';

// Activating refresh hooks
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['refresh_hooks'][] = 'tx_crawler_hooks_processCleanUp';

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

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\AOE\Crawler\Task\CrawlerQueueTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_im.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_im.description',
	'additionalFields' => '\AOE\Crawler\Task\CrawlerQueueTaskAdditionalFieldProvider'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\AOE\Crawler\Task\CrawlerTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_crawl.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_crawl.description',
	'additionalFields' => '\AOE\Crawler\Task\CrawlerTaskAdditionalFieldProvider'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\AOE\Crawler\Task\CrawlMultiProcessTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_crawlMultiProcess.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_crawl.description',
	'additionalFields' => '\AOE\Crawler\Task\CrawlMultiProcessTaskAdditionalFieldProvider'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\AOE\Crawler\Task\FlushQueueTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_flush.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_flush.description',
	'additionalFields' => '\AOE\Crawler\Task\FlushQueueTaskAdditionalFieldProvider'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['AOE\Crawler\Tasks\ProcessCleanupTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_processCleanup.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:crawler_processCleanup.description',
);
