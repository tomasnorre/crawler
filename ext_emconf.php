<?php

########################################################################
# Extension Manager/Repository config file for ext: "crawler"
#
# Auto generated 02-04-2009 12:25
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
	'version' => '3.0.5',
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
	'author' => 'Kasper Skaarhoj, Daniel Poetzinger, Fabrizio Branca, Tolleiv Nietsch, Timo Schmidt',
	'author_email' => 'dev@aoemedia.de',
	'author_company' => 'AOE media GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'realurl' => ''
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:24:"class.tx_crawler_lib.php";s:4:"da30";s:21:"ext_conf_template.txt";s:4:"c934";s:12:"ext_icon.gif";s:4:"a434";s:17:"ext_localconf.php";s:4:"132e";s:14:"ext_tables.php";s:4:"35c1";s:14:"ext_tables.sql";s:4:"35b7";s:16:"locallang_db.php";s:4:"ca54";s:12:"cli/conf.php";s:4:"a5ed";s:19:"cli/crawler_cli.php";s:4:"46a7";s:21:"cli/crawler_cli.phpsh";s:4:"8462";s:18:"cli/crawler_im.php";s:4:"e97b";s:12:"doc/TODO.txt";s:4:"aaa6";s:14:"doc/manual.sxw";s:4:"57e1";s:38:"modfunc1/class.tx_crawler_modfunc1.php";s:4:"a9ef";s:22:"modfunc1/locallang.php";s:4:"6652";}',
	'suggests' => array(
	),
);

?>
