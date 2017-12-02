<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler'] = [
    'EXT:crawler/cli/crawler_cli.php',
    '_CLI_crawler'
];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_im'] = [
    'EXT:crawler/cli/crawler_im.php',
    '_CLI_crawler'
];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_flush'] = [
    'EXT:crawler/cli/crawler_flush.php',
    '_CLI_crawler'
];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['crawler_multiprocess'] = [
    'EXT:crawler/cli/crawler_multiprocess.php',
    '_CLI_crawler'
];

\AOE\Crawler\Utility\HookUtility::registerHooks($_EXTKEY);
\AOE\Crawler\Utility\SchedulerUtility::registerSchedulerTasks($_EXTKEY);
\AOE\Crawler\Utility\BackendUtility::registerIcons();

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = true;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    $_EXTKEY,
    'auth' /* sv type */,
    'AOE\Crawler\Service\AuthenticationService' /* sv key */,
    [
        'title' => 'Login for wsPreview',
        'description' => '',

        'subtype' => 'getUserBE,authUserBE',

        'available' => true,
        'priority' => 80,
        'quality' => 50,

        'os' => '',
        'exec' => '',

        'className' => 'AOE\\Crawler\\Service\\AuthenticationService',
    ]
);
