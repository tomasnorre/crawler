<?php
defined('TYPO3_MODE') or die();

\AOE\Crawler\Utility\HookUtility::registerHooks($_EXTKEY);
\AOE\Crawler\Utility\SchedulerUtility::registerSchedulerTasks($_EXTKEY);
\AOE\Crawler\Utility\BackendUtility::registerIcons();

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = true;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    $_EXTKEY,
    'auth',
    'AOE\Crawler\Service\AuthenticationService',
    [
        'title' => 'Login for wsPreview',
        'description' => '',
        'subtype' => 'getUserBE,authUserBE',
        'available' => true,
        'priority' => 80,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'className' => \AOE\Crawler\Service\AuthenticationService::class,
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1566472321] = \AOE\Crawler\ContextMenu\ItemProvider::class;
