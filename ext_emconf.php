<?php

########################################################################
# Extension Manager/Repository config file for ext: "crawler"
#
# Auto generated 23-07-2008 21:25
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Site Crawler',
	'description' => 'Libraries and scripts for crawling the TYPO3 page tree. Used for re-caching, re-indexing, publishing applications etc.',
	'category' => 'module',
	'shy' => 0,
	'version' => '2.0.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj, Daniel Pötzinger - AOE media',
	'author_email' => 'kasper2005@typo3.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '3.0.0-0.0.0',
			'typo3' => '3.5.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:14:{s:24:"class.tx_crawler_lib.php";s:4:"dc2f";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"132e";s:14:"ext_tables.php";s:4:"35c1";s:14:"ext_tables.sql";s:4:"6370";s:16:"locallang_db.php";s:4:"ca54";s:12:"cli/conf.php";s:4:"a5ed";s:19:"cli/crawler_cli.php";s:4:"46a7";s:21:"cli/crawler_cli.phpsh";s:4:"8462";s:18:"cli/crawler_im.php";s:4:"e97b";s:12:"doc/TODO.txt";s:4:"aaa6";s:14:"doc/manual.sxw";s:4:"57e1";s:38:"modfunc1/class.tx_crawler_modfunc1.php";s:4:"a9ef";s:22:"modfunc1/locallang.php";s:4:"6652";}',
	'suggests' => array(
	),
);

?>