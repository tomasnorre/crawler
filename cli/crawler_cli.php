<?php
if (!defined('TYPO3_cliMode'))	die('You cannot run this script directly!');

require_once(t3lib_extMgm::extPath('crawler').'class.tx_crawler_lib.php');

$crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
$result= $crawlerObj->CLI_main();

if ($result===false) {
	exit(-1);	//unknown erroor
}
else if ($result ==0) {
	exit(1);	//success and finishes (no items remaining)
}
else {
	exit(0);	//sucess and items remaining
}

?>
