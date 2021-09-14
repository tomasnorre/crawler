<?php
defined('TYPO3_MODE') or die();

\TomasNorre\Crawler\Utility\HookUtility::registerHooks('crawler');
\TomasNorre\Crawler\Utility\BackendUtility::registerIcons();

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1566472321] = \TomasNorre\Crawler\ContextMenu\ItemProvider::class;

if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
    require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . '/Resources/Private/Php/Libraries/vendor/autoload.php';
}

// Remove me once I drop support for v9:
if (!class_exists(\TYPO3\CMS\Core\Domain\Repository\PageRepository::class)) {
    class_alias(\TYPO3\CMS\Frontend\Page\PageRepository::class, \TYPO3\CMS\Core\Domain\Repository\PageRepository::class, true);
}
