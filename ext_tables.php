<?php

use AOE\Crawler\Controller\Backend\BackendModuleStartCrawlingController;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::allowTableOnStandardPages('tx_crawler_configuration');

$typo3MajorVersion = (new Typo3Version())->getMajorVersion();
if ($typo3MajorVersion <= 11) {
    ExtensionManagementUtility::addModule(
        'web',
        'CrawlerStart',
        'after:web_info',
        '',
        [
            'routeTarget' => BackendModuleStartCrawlingController::class . '::handleRequest',
            'access' => 'user',
            'workspace' => 'online',
            'name' => 'web_CrawlerStart',
            'icon' => 'EXT:crawler/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf',
        ]
    );
}
