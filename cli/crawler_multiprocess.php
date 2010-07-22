<?php
if (!defined('TYPO3_cliMode'))	die('You cannot run this script directly!');

require_once(t3lib_extMgm::extPath('crawler').'domain/process/class.tx_crawler_domain_process_manager.php');
$processManager = new tx_crawler_domain_process_manager();
$processManager->multiProcess();


?>