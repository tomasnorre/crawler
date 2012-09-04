<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Crawler library, executed in a backend context
 *
 * @author Kasper Skaarhoej <kasperYYYY@typo3.com>
 */


/**
 * Cli basis:
 *
 * @author	Kasper Skaarhoej <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_crawler
 */
class tx_crawler_cli extends t3lib_cli {

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function tx_crawler_cli()	{

		// Running parent class constructor
		if (version_compare(TYPO3_version, '4.6.0', '>=')) {
			parent::__construct();
		} else {
			parent::t3lib_cli();
		}

		$this->cli_options[] = array('-h', 'Show the help', '');
		$this->cli_options[] = array('--help', 'Same as -h', '');
		$this->cli_options[] = array('--countInARun count', 'Amount of pages', 'How many pages should be crawled during that run.');
		$this->cli_options[] = array('--sleepTime milliseconds', 'Millisecounds to relax system during crawls', 'Amount of millisecounds which the system should use to relax between crawls.');
		$this->cli_options[] = array('--sleepAfterFinish seconds', 'Secounds to relax system after all crawls.', 'Amount of secounds which the system should use to relax after all crawls are done.');

		// Setting help texts:
		$this->cli_help['name'] = 'crawler CLI interface -- Crawling the URLs from the queue';
		$this->cli_help['synopsis'] = '###OPTIONS###';
		$this->cli_help['description'] = "";
		$this->cli_help['examples'] = "/.../cli_dispatch.phpsh crawler\nWill trigger the crawler which starts to process the queue entires\n";
		$this->cli_help['author'] = 'Kasper Skaarhoj, Daniel Poetzinger, Fabrizio Branca, Tolleiv Nietsch, Timo Schmidt - AOE media 2010';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/cli/class.tx_crawler_cli.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/cli/class.tx_crawler_cli.php']);
}

?>
