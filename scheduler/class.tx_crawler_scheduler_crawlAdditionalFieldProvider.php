<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 AOE media (dev@aoemedia.de)
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
 *
 * @author Tolleiv Nietsch <tolleiv.nietsch@aoemedia.de>
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @package
 * @version $Id:$
 */
class tx_crawler_scheduler_crawlAdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * render additional information fields within the scheduler backend
	 *
	 * @see interfaces/tx_scheduler_AdditionalFieldProvider#getAdditionalFields($taskInfo, $task, $schedulerModule)
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
		$additionalFields = array();

		if (empty($taskInfo['sleepTime'])) {
						if ($schedulerModule->CMD == 'add') {
								$taskInfo['sleepTime'] = 1000;
						} elseif ($schedulerModule->CMD == 'edit') {
								$taskInfo['sleepTime'] = $task->sleepTime;
						} else {
								$taskInfo['sleepTime'] = $task->sleepTime;
						}
		}

		if (empty($taskInfo['sleepAfterFinish'])) {
						if ($schedulerModule->CMD == 'add') {
								$taskInfo['sleepAfterFinish'] = 10;
						} elseif ($schedulerModule->CMD == 'edit') {
								$taskInfo['sleepAfterFinish'] = $task->sleepAfterFinish;
						} else {
								$taskInfo['sleepAfterFinish'] = $task->sleepAfterFinish;
						}
		}
		if (empty($taskInfo['countInARun'])) {
						if ($schedulerModule->CMD == 'add') {
								$taskInfo['countInARun'] = 100;
						} elseif ($schedulerModule->CMD == 'edit') {
								$taskInfo['countInARun'] = $task->countInARun;
						} else {
								$taskInfo['countInARun'] = $task->countInARun;
						}
		}

			// input for sleepTime
		$fieldID = 'task_sleepTime';
		$fieldCode  = '<input type="text" name="tx_scheduler[sleepTime]" id="' . $fieldID . '" value="' . htmlentities($taskInfo['sleepTime']) . '" />';
		$additionalFields[$fieldID] = array(
						'code'     => $fieldCode,
						'label'    => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.sleepTime'
				);
			// input for sleepAfterFinish
		$fieldID = 'task_sleepAfterFinish';
		$fieldCode  = '<input type="text" name="tx_scheduler[sleepAfterFinish]" id="' . $fieldID . '" value="' . htmlentities($taskInfo['sleepAfterFinish']) . '" />';
		$additionalFields[$fieldID] = array(
						'code'     => $fieldCode,
						'label'    => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.sleepAfterFinish'
				);
			// input for countInARun
		$fieldID = 'task_countInARun';
		$fieldCode  = '<input type="text" name="tx_scheduler[countInARun]" id="' . $fieldID . '" value="' . htmlentities($taskInfo['countInARun']) . '" />';
		$additionalFields[$fieldID] = array(
						'code'     => $fieldCode,
						'label'    => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.countInARun'
				);

		return $additionalFields;
	}

	/**
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_module1	$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		$isValid = false;

		if ( tx_crawler_api::convertToPositiveInteger($submittedData['sleepTime']) > 0 ) {
			$isValid = true;
		} else {
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidSleepTime'), t3lib_FlashMessage::ERROR);
		}

		if ( tx_crawler_api::convertToPositiveInteger($submittedData['sleepAfterFinish']) === 0 ) {
			$isValid = false;
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidSleepAfterFinish'), t3lib_FlashMessage::ERROR);
		}

		if ( tx_crawler_api::convertToPositiveInteger($submittedData['countInARun']) === 0 ) {
			$isValid = false;
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidCountInARun'), t3lib_FlashMessage::ERROR);
		}

		return $isValid;
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches
	 *
	 * @param	array				$submittedData: array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task: reference to the current task object
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {

		$task->sleepTime        = intval($submittedData['sleepTime']);
		$task->sleepAfterFinish = intval($submittedData['sleepAfterFinish']);
		$task->countInARun      = intval($submittedData['countInARun']);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_scheduler_crawlAdditionalFieldProvider.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_scheduler_crawlAdditionalFieldProvider.php']);
}

?>
