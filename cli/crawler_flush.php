<?php
if (!defined('TYPO3_REQUESTTYPE_CLI')) {
    die('You cannot run this script directly!');
}

$crawlerController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\AOE\Crawler\Controller\CrawlerController::class);
$crawlerController->CLI_main_flush($_SERVER["argv"]);
