<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 AOE GmbH <dev@aoe.com>
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
 * Class tx_crawler_scheduler_crawlMultiProcessAdditionalFieldProvider
 *
 * @package AOE\Crawler\Task
 */
class tx_crawler_scheduler_crawlMultiProcessAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param array $taskInfo
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule
	 * @return array
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
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
		$fieldId = 'task_timeOut';
		$fieldCode = '<input type="text" name="tx_scheduler[timeOut]" id="' . $fieldId . '" value="' . htmlentities($taskInfo['timeOut']) . '" />';
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.timeOut'
		);

		return $additionalFields;
	}

	/**
	 * Validates the additional fields' values
	 *
	 * @param array $submittedData
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule
	 * @return bool
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$isValid = FALSE;

		if (tx_crawler_api::convertToPositiveInteger($submittedData['timeOut']) > 0) {
			$isValid = TRUE;
		} else {
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidTimeOut'), t3lib_FlashMessage::ERROR);
		}

		return $isValid;
	}

	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param array $submittedData
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$task->timeOut = intval($submittedData['timeOut']);
	}
}
