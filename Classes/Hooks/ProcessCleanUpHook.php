<?php
namespace AOE\Crawler\Hooks;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ProcessCleanUpHook
 * @package AOE\Crawler\Hooks
 */
class ProcessCleanUpHook
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private $db;

    /**
     * @var \tx_crawler_lib
     */
    private $crawlerLib;

    /**
     * @var array
     */
    private $extensionSettings;

    /**
     * Main function of process CleanUp Hook.
     *
     * @param \tx_crawler_lib $crawlerLib Crawler Lib class
     *
     * @return void
     */
    public function crawler_init(\tx_crawler_lib $crawlerLib)
    {
        $this->db = $GLOBALS['TYPO3_DB'];
        $this->crawlerLib = $crawlerLib;
        $this->extensionSettings = $this->crawlerLib->extensionSettings;

        // Clean Up
        $this->removeActiveOrphanProcesses();
        $this->removeActiveProcessesOlderThanOneHour();
    }

    /**
     * Remove active processes older than one hour
     *
     * @return void
     */
    private function removeActiveProcessesOlderThanOneHour()
    {
        $results = $this->db->exec_SELECTgetRows(
            'process_id, system_process_id',
            'tx_crawler_process',
            'ttl <= ' . intval(time() - $this->extensionSettings['processMaxRunTime'] - 3600) . ' AND active = 1'
        );

        if (!is_array($results)) {
            return;
        }
        foreach ($results as $result) {
            $systemProcessId = (int)$result['system_process_id'];
            $processId = $result['process_id'];
            if ($systemProcessId > 1) {
                if ($this->doProcessStillExists($systemProcessId)) {
                    $this->killProcess($systemProcessId);
                }
                $this->removeProcessFromProcesslist($processId);
            }
        }
    }

    /**
     * Removes active orphan processes from process list
     *
     * @return void
     */
    private function removeActiveOrphanProcesses()
    {
        $results = $this->db->exec_SELECTgetRows(
            'process_id, system_process_id',
            'tx_crawler_process',
            'ttl <= ' . intval(time() - $this->extensionSettings['processMaxRunTime']) . ' AND active = 1'
        );

        if (!is_array($results)) {
            return;
        }
        foreach ($results as $result) {
            $processExists = false;
            $systemProcessId = (int)$result['system_process_id'];
            $processId = $result['process_id'];
            if ($systemProcessId > 1) {
                $dispatcherProcesses = $this->findDispatcherProcesses();
                if (!is_array($dispatcherProcesses) || empty($dispatcherProcesses)) {
                    $this->removeProcessFromProcesslist($processId);
                    return;
                }
                foreach ($dispatcherProcesses as $process) {
                    $responseArray = $this->createResponseArray($process);
                    if ($systemProcessId === (int)$responseArray[1]) {
                        $processExists = true;
                    };
                }
                if (!$processExists) {
                    $this->removeProcessFromProcesslist($processId);
                }
            }
        }
    }

    /**
     * Remove a process from processlist
     *
     * @param string $processId Unique process Id.
     *
     * @return void
     */
    private function removeProcessFromProcesslist($processId)
    {
        $this->db->exec_DELETEquery(
            'tx_crawler_process',
            'process_id = ' . $this->db->fullQuoteStr($processId, 'tx_crawler_process')
        );
    }

    /**
     * Create response array
     * Convert string to array with space character as delimiter,
     * removes all empty records to have a cleaner array
     *
     * @param string $string String to create array from
     *
     * @return array
     *
     * TODO: Write Unit test
     */
    private function createResponseArray($string)
    {
        $responseArray = GeneralUtility::trimExplode(' ', $string, true);
        $responseArray = array_values($responseArray);
        return $responseArray;
    }

    /**
     * Check if the process still exists
     *
     * @param int $pid Process id to be checked.
     *
     * @return bool
     */
    private function doProcessStillExists($pid)
    {
        $doProcessStillExists = false;
        if (!$this->isOsWindows()) {
            // Not windows
            if (file_exists('/proc/' . $pid)) {
                $doProcessStillExists = true;
            }
        } else {
            // Windows
            exec('tasklist | find "' . $pid . '"', $returnArray, $returnValue);
            if (count($returnArray) > 0 && preg_match('/php/i', $returnValue[0])) {
                $doProcessStillExists = true;
            }
        }
        return $doProcessStillExists;
    }

    /**
     * Kills a process
     *
     * @param int $pid Process id to kill
     *
     * @return void
     */
    private function killProcess($pid)
    {
        if (!$this->isOsWindows()) {
            // Not windows
            posix_kill($pid, 9);
        } else {
            // Windows
            exec('taskkill /PID ' . $pid);
        }
    }

    /**
     * Find dispatcher processes
     *
     * @return array
     */
    private function findDispatcherProcesses()
    {
        $returnArray = array();
        if (!$this->isOsWindows()) {
            // Not windows
            exec('ps aux | grep \'cli_dispatcher\'', $returnArray, $returnValue);
        } else {
            // Windows
            exec('tasklist | find \'cli_dispatcher\'', $returnArray, $returnValue);
        }
        return $returnArray;
    }

    /**
     * Check if OS is Windows
     *
     * @return bool
     */
    private function isOsWindows()
    {
        if (TYPO3_OS === '') {
            return true;
        }
        return false;
    }
}