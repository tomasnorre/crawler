<?php
if (!defined('TYPO3_REQUESTTYPE_CLI')) {
    die('You cannot run this script directly!');
}

$crawlerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\AOE\Crawler\Controller\CrawlerController::class);
$crawlerObj->CLI_main_im($_SERVER["argv"]);
