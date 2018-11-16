<?php
namespace AOE\Crawler\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

use TYPO3\CMS\Core\Controller\CommandLineController;

/**
 * Class FlushCommandLineController
 *
 * @package AOE\Crawler\Command
 * @codeCoverageIgnore
 *
 * @deprecated since crawler v6.2.2, will be removed in crawler v7.0.0.
 */
class FlushCommandLineController extends CommandLineController
{

    /**
     * Constructor
     *
     * @deprecated since crawler v6.2.2, will be removed in crawler v7.0.0.
     */
    public function __construct()
    {
        parent::__construct();

        // Adding options to help archive:
        $this->cli_options[] = ['-o mode', 'Output mode: "finished", "all", "pending"', "Specifies the type queue entries which is flushed in the process."];
        #		$this->cli_options[] = array('-v level', 'Verbosity level 0-3', "The value of level can be:\n  0 = all output\n  1 = info and greater (default)\n  2 = warnings and greater\n  3 = errors");

        // Setting help texts:
        $this->cli_help['name'] = 'crawler CLI interface -- Cleaning up the queue.';
        $this->cli_help['synopsis'] = 'page_id ###OPTIONS###';
        $this->cli_help['description'] = "Works as a CLI interface to some functionality from the Web > Info > Site Crawler module; It will remove queue entires and perform a cleanup.";
        $this->cli_help['examples'] = "/.../cli_dispatch.phpsh crawler_flush 5 -o=finished\nWill remove all finished queue-entries in the sub-branch of page 5\n";
        $this->cli_help['examples'] = "/.../cli_dispatch.phpsh crawler_flush 0 -o=all\nWill remove all queue-entries for every page\n";
        $this->cli_help['author'] = 'Kasper Skaarhoj, Daniel Poetzinger, Fabrizio Branca, Tolleiv Nietsch, Timo Schmidt - AOE media 2009';
    }
}
