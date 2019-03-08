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
 * Class CrawlerCommandLineController
 *
 * @package AOE\Crawler\Command
 * @codeCoverageIgnore
 *
 * @deprecated since crawler v6.2.1, will be removed in crawler v7.0.0.
 */
class CrawlerCommandLineController extends CommandLineController
{

    /**
     * Constructor
     *
     * @deprecated since crawler v6.2.1, will be removed in crawler v7.0.0.
     */
    public function __construct()
    {
        parent::__construct();

        $this->cli_options[] = ['-h', 'Show the help', ''];
        $this->cli_options[] = ['--help', 'Same as -h', ''];
        $this->cli_options[] = ['--countInARun count', 'Amount of pages', 'How many pages should be crawled during that run.'];
        $this->cli_options[] = ['--sleepTime milliseconds', 'Millisecounds to relax system during crawls', 'Amount of millisecounds which the system should use to relax between crawls.'];
        $this->cli_options[] = ['--sleepAfterFinish seconds', 'Secounds to relax system after all crawls.', 'Amount of secounds which the system should use to relax after all crawls are done.'];

        // Setting help texts:
        $this->cli_help['name'] = 'crawler CLI interface -- Crawling the URLs from the queue';
        $this->cli_help['synopsis'] = '###OPTIONS###';
        $this->cli_help['description'] = "";
        $this->cli_help['examples'] = "/.../cli_dispatch.phpsh crawler\nWill trigger the crawler which starts to process the queue entires\n";
        $this->cli_help['author'] = 'Kasper Skaarhoj, Daniel Poetzinger, Fabrizio Branca, Tolleiv Nietsch, Timo Schmidt - AOE media 2010';
    }
}
