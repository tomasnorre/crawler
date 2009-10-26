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
 * @package
 * @version $Id:$
 */
class tx_crawler_scheduler_AdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {
	/**
	 * render additional information fields within the scheduler backend
	 *
	 * @see interfaces/tx_scheduler_AdditionalFieldProvider#getAdditionalFields($taskInfo, $task, $schedulerModule)
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
/*
			// Initialize extra field value
		if (empty($taskInfo['sleepTime'])) {
			if ($parentObject->CMD == 'add') {
					// In case of new task and if field is empty, set default sleep time
				$taskInfo['sleepTime'] = 30;
			} else if ($parentObject->CMD == 'edit') {
					// In case of edit, set to internal value if no data was submitted already
				$taskInfo['sleepTime'] = $task->sleepTime;
			} else {
					// Otherwise set an empty value, as it will not be used anyway
				$taskInfo['sleepTime'] = '';
			}
		}

			// Write the code for the field
		$fieldID = 'task_sleepTime';
		$fieldCode = '<input type="text" name="tx_scheduler[sleepTime]" id="' . $fieldID . '" value="' . $taskInfo['sleepTime'] . '" size="10" />';
*/
		$additionalFields = array();
/*		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:scheduler/mod1/locallang.xml:label.sleepTime',
			'cshKey'   => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldID
		);
*/
		return $additionalFields;
	}

	/**
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_module1	$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		return true;
/*		$submittedData['sleepTime'] = intval($submittedData['sleepTime']);

		if ($submittedData['sleepTime'] < 0) {
			$parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:msg.invalidSleepTime'), t3lib_FlashMessage::ERROR);
			$result = false;
		} else {
			$result = true;
		}
		return $result;
*/
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches
	 *
	 * @param	array				$submittedData: array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task: reference to the current task object
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->sleepTime = $submittedData['sleepTime'];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/scheduler/examples/class.tx_scheduler_sleeptask_additionalfieldprovider.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/scheduler/examples/class.tx_scheduler_sleeptask_additionalfieldprovider.php']);
}

?>