<?php
defined('TYPO3_MODE') or die();

if ('BE' === TYPO3_MODE) {
    \AOE\Crawler\Utility\BackendUtility::registerInfoModuleFunction();
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_crawler_configuration');
}


TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
    module.tx_dashboard {
        view {
            templateRootPaths.1596545239 = EXT:crawler/Resources/Private/Templates/Widget/
            partialRootPaths.1596545239 = EXT:crawler/Resources/Private/Partials/Widget/
            layoutRootPaths.1596545239 = EXT:crawler/Resources/Private/Layouts/Widget/
        }
    }'
);
