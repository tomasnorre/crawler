<?php
defined('TYPO3') or die();

\AOE\Crawler\Utility\HookUtility::registerHooks('crawler');

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1566472321] = \AOE\Crawler\ContextMenu\ItemProvider::class;

if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
    require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . '/Resources/Private/Php/Libraries/vendor/autoload.php';
}

$packageManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);

if ($packageManager->isPackageActive('indexed_search')) {
// Register with "indexed_search" extension
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions']['indexed_search'] = [
        'key' => 'tx_indexedsearch_reindex',
        'value' => 'Re-indexing'
    ];
}


