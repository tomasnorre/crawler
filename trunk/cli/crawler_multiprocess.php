<?php
if (!defined('TYPO3_cliMode'))	die('You cannot run this script directly!');

require_once(t3lib_extMgm::extPath('crawler').'domain/process/class.tx_crawler_domain_process_manager.php');
$processManager = new tx_crawler_domain_process_manager();
$timeout = isset($_SERVER["argv"][1])?intval($_SERVER["argv"][1]):10000;
try {
	$processManager->multiProcess($timeout);
}
catch (Exception $e) {
	echo chr(10).$e->getMessage();
}
