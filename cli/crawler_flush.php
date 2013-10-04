<?php
if (!defined('TYPO3_cliMode'))	die('You cannot run this script directly!');

$crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
$crawlerObj->CLI_main_flush($_SERVER["argv"]);

?>
