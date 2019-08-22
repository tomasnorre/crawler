<?php
defined('TYPO3_MODE') or die();

if ('BE' === TYPO3_MODE) {
    \AOE\Crawler\Utility\BackendUtility::registerInfoModuleFunction();
    \AOE\Crawler\Utility\BackendUtility::registerClickMenuItem();
    \AOE\Crawler\Utility\BackendUtility::registerContextSensitiveHelpForTcaFields();
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_crawler_configuration');
}
