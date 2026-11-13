<?php
defined('TYPO3') or die();

// Crawl messages are sent to the "crawler" transport (see Configuration/Services.yaml) by default and are
// processed by a worker: `vendor/bin/typo3 messenger:consume crawler`.
// The routing can be overridden in config/system/settings.php or config/system/additional.php.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][\AOE\Crawler\Queue\Message\CrawlUrlMessage::class] ??= 'crawler';

if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
    require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . '/Resources/Private/Php/Libraries/vendor/autoload.php';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval'][] =
    \AOE\Crawler\Hooks\DataHandlerHook::class . '->addFlushedPagesToCrawlerQueue';

$packageManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);

if ($packageManager->isPackageActive('indexed_search')) {
// Register with "indexed_search" extension
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions']['indexed_search'] = [
        'key' => 'tx_indexedsearch_reindex',
        'value' => 'Re-indexing'
    ];
}


