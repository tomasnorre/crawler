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
class tx_crawler_cli_im extends t3lib_cli {

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function tx_crawler_cli_im()	{

		// Running parent class constructor
		if (version_compare(TYPO3_version, '4.6.0', '>=')) {
			parent::__construct();
		} else {
			parent::t3lib_cli();
		}

		// Adding options to help archive:
		/**
		* We removed the "proc" option as it seemd not to be working any more. But as the complete handling of the crawler has changed regarding the configuration
		* this is completely ok. Since configuration records were introduced to configure "what should be done" additionally to page ts the way to setup jobs
		* has drifted from selecting filtering processing instructions to selecting/filtering configuration keys (you can configure the processing instructions there).
		* This is also reflected in the backend modules and allows you a much clearer and powerful way to work with the crawler extension.
		*/
		// $this->cli_options[] = array('-proc listOfProcInstr', 'Comma list of processing instructions. These are the "actions" carried out when crawling and you must specify at least one. Depends on third-party extensions. Examples are "tx_cachemgm_recache" from "cachemgm" extension (will recache pages), "tx_staticpub_publish" from "staticpub" (publishing pages to static files) or "tx_indexedsearch_reindex" from "indexed_search" (indexes pages).');
		// TODO: cleanup here!
		$this->cli_options[] = array('-d depth', 'Tree depth, 0-99', "How many levels under the 'page_id' to include.");
		$this->cli_options[] = array('-o mode', 'Output mode: "url", "exec", "queue"', "Specifies output modes\nurl : Will list URLs which wget could use as input.\nqueue: Will put entries in queue table.\nexec: Will execute all entries right away!");
		$this->cli_options[] = array('-n number', 'Number of items per minute.', 'Specifies how many items are put in the queue per minute. Only valid for output mode "queue"');
		$this->cli_options[] = array('-conf configurationkeys','List of Configuration Keys','A commaseperated list of crawler configurations');
		#		$this->cli_options[] = array('-v level', 'Verbosity level 0-3', "The value of level can be:\n  0 = all output\n  1 = info and greater (default)\n  2 = warnings and greater\n  3 = errors");

		// Setting help texts:
		$this->cli_help['name'] = 'crawler CLI interface -- Submitting URLs to be crawled via CLI interface.';
		$this->cli_help['synopsis'] = 'page_id ###OPTIONS###';
		$this->cli_help['description'] = "Works as a CLI interface to some functionality from the Web > Info > Site Crawler module; It can put entries in the queue from command line options, return the list of URLs and even execute all entries right away without having to queue them up - this can be useful for immediate re-cache, re-indexing or static publishing from command line.";
		$this->cli_help['examples'] = "/.../cli_dispatch.phpsh crawler_im 7 -d=2 -conf=<configurationKey> -o=exec\nWill re-cache pages from page 7 and two levels down, executed immediately.\n";
		$this->cli_help['examples'].= "/.../cli_dispatch.phpsh crawler_im 7 -d=0 -conf=<configurationKey> -n=4 -o=queue\nWill put entries for re-caching pages from page 7 into queue, 4 every minute.\n";
		$this->cli_help['author'] = 'Kasper Skaarhoj, Daniel Poetzinger, Fabrizio Branca, Tolleiv Nietsch, Timo Schmidt - AOE media 2009';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/cli/class.tx_crawler_cli_im.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/cli/class.tx_crawler_cli_im.php']);
}

?>