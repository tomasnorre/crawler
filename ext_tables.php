<?php
defined('TYPO3_MODE') or die();

if ('BE' === TYPO3_MODE) {
    \AOE\Crawler\Utility\BackendUtility::registerInfoModuleFunction();
    \AOE\Crawler\Utility\BackendUtility::registerClickMenuItem();
    \AOE\Crawler\Utility\BackendUtility::registerContextSensitiveHelpForTcaFields();
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_crawler_configuration');

    $isVersion7 = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 8000000;
    if ($isVersion7) {
        $mappings = ['core', 'general'];
        foreach ($mappings as $mapping) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:lang/Resources/Private/Language/locallang_' . $mapping . '.xlf'][] = 'EXT:lang/locallang_' . $mapping . '.xlf';
        }
    }
}
