<?php

########################################################################
# Extension Manager/Repository config file for ext "crawler".
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Site Crawler',
	'description' => 'Libraries and scripts for crawling the TYPO3 page tree. Used for re-caching, re-indexing, publishing applications etc.',
	'category' => 'module',
	'shy' => 0,
	'version' => '4.0.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj, Daniel Poetzinger, Fabrizio Branca, Tolleiv Nietsch, Timo Schmidt, Michael Klapper, Stefan Rotsch, Tomas Norre Mikkelsen',
	'author_email' => 'dev@aoe.com',
	'author_company' => 'AOE GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-5.6.99',
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => '',
	'suggests' => array(
	),
);
