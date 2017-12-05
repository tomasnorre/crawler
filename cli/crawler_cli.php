<?php
if (!defined('TYPO3_REQUESTTYPE_CLI')) {
    die('You cannot run this script directly!');
}

$crawlerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_crawler_lib');

exit($crawlerObj->CLI_main());
