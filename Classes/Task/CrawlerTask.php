<?php
namespace AOE\Crawler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
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

use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class CrawlerTask
 *
 * @package AOE\Crawler\Task
 */
class CrawlerTask extends AbstractTask
{

    /**
     * @var integer
     */
    public $sleepTime;

    /**
     * @var integer
     */
    public $sleepAfterFinish;

    /**
     * @var integer
     */
    public $countInARun;

    /**
     * Function executed from the Scheduler.
     *
     * @return bool
     */

    public function execute()
    {
        $this->setCliArguments();

        /* @var $crawlerObject \tx_crawler_lib */
        $crawlerObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_crawler_lib');
        $crawlerObject->CLI_main();
        return true;
    }

    /**
     * Simulate cli call with setting the required options to the $_SERVER['argv']
     *
     * @access protected
     * @return void
     */
    protected function setCliArguments()
    {
        $_SERVER['argv'] = [$_SERVER['argv'][0], '0', '-ss', '--sleepTime', $this->sleepTime, '--sleepAfterFinish', $this->sleepAfterFinish, '--countInARun', $this->countInARun];
    }
}
