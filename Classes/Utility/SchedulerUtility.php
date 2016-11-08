<?php
namespace AOE\Crawler\Utility;

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

/**
 * Class SchedulerUtility
 * @package AOE\Crawler\Utility
 */
class SchedulerUtility
{
    /**
     * @param $extKey
     *
     * @return void
     */
    public static function registerSchedulerTasks($extKey)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\AOE\Crawler\Task\CrawlerQueueTask'] = array(
            'extension'        => $extKey,
            'title'            => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_im.name',
            'description'      => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_im.description',
            'additionalFields' => '\AOE\Crawler\Task\CrawlerQueueTaskAdditionalFieldProvider'
        );
        
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\AOE\Crawler\Task\CrawlerTask'] = array(
            'extension'        => $extKey,
            'title'            => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_crawl.name',
            'description'      => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_crawl.description',
            'additionalFields' => '\AOE\Crawler\Task\CrawlerTaskAdditionalFieldProvider'
        );
        
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\AOE\Crawler\Task\CrawlMultiProcessTask'] = array(
            'extension'        => $extKey,
            'title'            => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_crawlMultiProcess.name',
            'description'      => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_crawl.description',
            'additionalFields' => '\AOE\Crawler\Task\CrawlMultiProcessTaskAdditionalFieldProvider'
        );
        
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['\AOE\Crawler\Task\FlushQueueTask'] = array(
            'extension'        => $extKey,
            'title'            => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_flush.name',
            'description'      => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_flush.description',
            'additionalFields' => '\AOE\Crawler\Task\FlushQueueTaskAdditionalFieldProvider'
        );
        
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['AOE\Crawler\Tasks\ProcessCleanupTask'] = array(
            'extension'        => $extKey,
            'title'            => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_processCleanup.name',
            'description'      => 'LLL:EXT:' . $extKey . '/locallang_db.xml:crawler_processCleanup.description',
        );
    }
}