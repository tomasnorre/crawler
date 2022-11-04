<?php
defined('TYPO3') or die();

\AOE\Crawler\Utility\HookUtility::registerHooks('crawler');
\AOE\Crawler\Utility\BackendUtility::registerIcons();

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1566472321] = \AOE\Crawler\ContextMenu\ItemProvider::class;

#if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
#    require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . 'Resources/Private/Php/Libraries/vendor/autoload.php';
#}

# This is done to ensure that ProcInstructions are loaded correctly under TYPO3 11, as the CrawlerHooks etc is
# removed in Indexed Search in version 11.
$packageManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
$typo3Version = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Information\Typo3Version::class);

if ($packageManager->isPackageActive('indexed_search') && $typo3Version->getMajorVersion() === 11) {
// Register with "indexed_search" extension
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions']['indexed_search'] = [
        'key' => 'tx_indexedsearch_reindex',
        'value' => 'Re-indexing'
    ];
}
