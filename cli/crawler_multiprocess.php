<?php
if (!defined('TYPO3_cliMode')) {
	die('You cannot run this script directly!');
}

$processManager = new tx_crawler_domain_process_manager();
$timeout = isset($_SERVER['argv'][1] ) ? intval($_SERVER['argv'][1]) : 10000;

try {
	$processManager->multiProcess($timeout);
} catch (Exception $e) {
	echo PHP_EOL . $e->getMessage();
}
