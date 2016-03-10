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
 * Class tx_crawler_scheduler_flushAdditionalFieldProvider
 *
 * @package AOE\Crawler\Task
 */
class tx_crawler_scheduler_flushAdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param array $taskInfo
	 * @param tx_scheduler_Task $task
	 * @param tx_scheduler_Module $schedulerModule
	 * @return array
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
		$additionalFields = array();
		// Initialize extra field value
		if (empty($taskInfo['mode'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['mode'] = 'finished';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['mode'] = $task->mode;
			} else {
				$taskInfo['mode'] = $task->mode;
			}
		}

		$fieldId = 'mode';
		$fieldCode = '<select name="tx_scheduler[mode]" id="' . $fieldId . '" value="' . htmlentities($taskInfo['mode']) . '">'
			. '<option value="all"' . ($taskInfo['mode'] == 'all' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_flush.modeAll') . '</option>'
			. '<option value="finished"' . ($taskInfo['mode'] == 'finished' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_flush.modeFinished') . '</option>'
			. '<option value="pending"' . ($taskInfo['mode'] == 'pending' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_flush.modePending') . '</option>'
			. '</select>';

		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => 'LLL:EXT:crawler/locallang_db.xml:crawler_flush.mode'
		);

		return $additionalFields;
	}

	/**
	 * Validates the additional fields' values
	 *
	 * @param array $submittedData
	 * @param tx_scheduler_Module $schedulerModule
	 * @return bool
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		return in_array($submittedData['mode'], array('all', 'pending', 'finished'));
	}

	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param array $submittedData
	 * @param tx_scheduler_Task $task
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->mode = $submittedData['mode'];
	}
}
