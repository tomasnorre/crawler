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
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @package
 * @version $Id:$
 */
class tx_crawler_scheduler_crawlMultiProcessAdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * render additional information fields within the scheduler backend
	 *
	 * @see interfaces/tx_scheduler_AdditionalFieldProvider#getAdditionalFields($taskInfo, $task, $schedulerModule)
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 * @param array $taskInfo
	 * @param $task
	 * @param \tx_scheduler_Module $schedulerModule
	 * @return array
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
		$additionalFields = array();

		if (empty($taskInfo['timeOut'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['timeOut'] = 10000;
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['timeOut'] = $task->timeOut;
			} else {
				$taskInfo['timeOut'] = $task->timeOut;
			}
		}

		// input for timeOut
		$fieldID = 'task_timeOut';
		$fieldCode = '<input type="text" name="tx_scheduler[timeOut]" id="' . $fieldID . '" value="' . htmlentities($taskInfo['timeOut']) . '" />';
		$additionalFields[$fieldID] = array(
			'code' => $fieldCode,
			'label' => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.timeOut'
		);

		return $additionalFields;
	}

	/**
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param \tx_scheduler_Module $schedulerModule
	 * @internal param \tx_scheduler_module1 $parentObject : reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		$isValid = false;

		if (tx_crawler_api::convertToPositiveInteger($submittedData['timeOut']) > 0) {
			$isValid = true;
		} else {
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidTimeOut'), t3lib_FlashMessage::ERROR);
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

		$task->timeOut = intval($submittedData['timeOut']);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_scheduler_crawlMultiProcessAdditionalFieldProvider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_scheduler_crawlMultiProcessAdditionalFieldProvider.php']);
}

?>
