<?php
if (!defined('TYPO3_REQUESTTYPE_CLI')) {
    die('You cannot run this script directly!');
}

$processService = new \AOE\Crawler\Service\ProcessService();
$timeout = isset($_SERVER['argv'][1]) ? intval($_SERVER['argv'][1]) : 1800;

try {
    $processService->multiProcess($timeout);
} catch (Exception $e) {
    echo PHP_EOL . $e->getMessage();
}
