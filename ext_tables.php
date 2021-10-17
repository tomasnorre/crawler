<?php
defined('TYPO3') or die();

if ('BE' === TYPO3_MODE) {
    \AOE\Crawler\Utility\BackendUtility::registerInfoModuleFunction();
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_crawler_configuration');
}
