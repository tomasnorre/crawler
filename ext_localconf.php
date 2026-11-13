<?php
defined('TYPO3') or die();

if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
    require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . '/Resources/Private/Php/Libraries/vendor/autoload.php';
}

// Crawl messages are sent to the crawler transports (see Configuration/Services.yaml) by default, the transport is
// chosen based on the priority of the page (AOE\Crawler\Queue\CrawlUrlMessageDispatcher). They are processed by a
// worker, which handles higher priorities first: `vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low`.
// The routing can be overridden in config/system/settings.php or config/system/additional.php.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][\AOE\Crawler\Queue\Message\CrawlUrlMessage::class] ??= 'crawler';

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
