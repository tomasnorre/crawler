<?php
if (!defined('TYPO3_REQUESTTYPE_CLI')) {
    die('You cannot run this script directly!');
}

$processManager = new tx_crawler_domain_process_manager();
$timeout = isset($_SERVER['argv'][1]) ? intval($_SERVER['argv'][1]) : 1800;

try {
    $processManager->multiProcess($timeout);
} catch (Exception $e) {
    echo PHP_EOL . $e->getMessage();
}
