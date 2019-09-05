<?php
defined('TYPO3_MODE') or die();

\AOE\Crawler\Utility\HookUtility::registerHooks($_EXTKEY);
\AOE\Crawler\Utility\SchedulerUtility::registerSchedulerTasks($_EXTKEY);
\AOE\Crawler\Utility\BackendUtility::registerIcons();

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1566472321] = \AOE\Crawler\ContextMenu\ItemProvider::class;
