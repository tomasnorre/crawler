<?php
defined('TYPO3_MODE') or die();

\AOE\Crawler\Utility\HookUtility::registerHooks('crawler');
\AOE\Crawler\Utility\BackendUtility::registerIcons();

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1566472321] = \AOE\Crawler\ContextMenu\ItemProvider::class;

if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
    require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . '/Resources/Private/Php/Libraries/vendor/autoload.php';
}
