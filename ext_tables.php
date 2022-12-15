<?php

use AOE\Crawler\Controller\Backend\BackendModuleCrawlerLogController;
use AOE\Crawler\Controller\Backend\BackendModuleCrawlerProcessController;
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
        null,
        [
            'routeTarget' => BackendModuleStartCrawlingController::class . '::handleRequest',
            'access' => 'user',
            'workspace' => 'online',
            'name' => 'web_CrawlerStart',
            'icon' => 'EXT:crawler/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf',
        ]
    );

    ExtensionManagementUtility::addModule(
        'CrawlerStart',
        'CrawlerLog',
        'after:web_info',
        null,
        [
            'routeTarget' => BackendModuleCrawlerLogController::class . '::handleRequest',
            'access' => 'user',
            'workspace' => 'online',
            'name' => 'web_CrawlerStart',
            'icon' => 'EXT:crawler/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf',
        ]
    );

    ExtensionManagementUtility::addModule(
        'CrawlerStart',
        'CrawlerProcess',
        'after:web_info',
        null,
        [
            'routeTarget' => BackendModuleCrawlerProcessController::class . '::handleRequest',
            'access' => 'user',
            'workspace' => 'online',
            'name' => 'web_CrawlerStart',
            'icon' => 'EXT:crawler/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf',
        ]
    );
}
