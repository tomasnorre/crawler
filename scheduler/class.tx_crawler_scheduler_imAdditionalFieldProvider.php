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
 * Class tx_crawler_scheduler_imAdditionalFieldProvider
 *
 * @package AOE\Crawler\Task
 */
class tx_crawler_scheduler_imAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

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

		if (empty($taskInfo['configuration'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['configuration'] = array();
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['configuration'] = $task->configuration;
			} else {
				$taskInfo['configuration'] = $task->configuration;
			}
		}

		if (empty($taskInfo['startPage'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['startPage'] = 0;
				if ($task instanceof \TYPO3\CMS\Scheduler\Task\AbstractTask) {
					$task->startPage = 0;
				}
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['startPage'] = $task->startPage;
			} else {
				$taskInfo['startPage'] = $task->startPage;
			}
		}

		if (empty($taskInfo['depth'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['depth'] = array();
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['depth'] = $task->depth;
			} else {
				$taskInfo['depth'] = $task->depth;
			}
		}

		// input for startPage
		$fieldId = 'task_startPage';
		$fieldCode = '<input name="tx_scheduler[startPage]" type="text" id="' . $fieldId . '" value="' . $task->startPage . '" />';
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.startPage'
		);

		// input for depth
		$fieldId = 'task_depth';
		$fieldValueArray = array(
			'0' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_0'),
			'1' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_1'),
			'2' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_2'),
			'3' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_3'),
			'4' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_4'),
			'99' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_infi'),
		);
		$fieldCode = '<select name="tx_scheduler[depth]" id="' . $fieldId . '">';

		foreach ($fieldValueArray as $key => $label) {
			$fieldCode .= "\t" . '<option value="' . $key . '"' . (($key == $task->depth) ? ' selected="selected"' : '') . '>' . $label . '</option>';
		}

		$fieldCode .= '</select>';
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.depth'
		);

		// combobox for configuration records
		$recordsArray = $this->getCrawlerConfigurationRecords();
		$fieldId = 'task_configuration';
		$fieldCode = '<select name="tx_scheduler[configuration][]" multiple="multiple" id="' . $fieldId . '">';
		$fieldCode .= "\t" . '<option value=""></option>';
		for ($i = 0; $i < count($recordsArray); $i++) {
			$fieldCode .= "\t" . '<option ' . $this->getSelectedState($task->configuration, $recordsArray[$i]['name']) . 'value="' . $recordsArray[$i]['name'] . '">' . $recordsArray[$i]['name'] . '</option>';
		}
		$fieldCode .= '</select>';

		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.conf'
		);

		return $additionalFields;
	}

	/**
	 * Mark current value as selected by returning the "selected" attribute
	 *
	 * @access protected
	 * @param $configurationArray
	 * @param $currentValue
	 * @return string
	 */
	protected function getSelectedState($configurationArray, $currentValue) {
		$selected = '';
		for ($i = 0; $i < count($configurationArray); $i++) {
			if (strcmp($configurationArray[$i], $currentValue) === 0) {
				$selected = 'selected="selected" ';
			}
		}

		return $selected;
	}

	/**
	 * Get all available configuration records.
	 *
	 * @access protected
	 * @return array which contains the available configuration records.
	 */
	protected function getCrawlerConfigurationRecords() {
		$records = array();
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_crawler_configuration',
			'1=1' . t3lib_BEfunc::deleteClause('tx_crawler_configuration')
		);

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$records[] = $row;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($result);

		return $records;
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

		//!TODO add validation to validate the $submittedData['configuration'] wich is normally a comma seperated string
		if (is_array($submittedData['configuration'])) {
			$isValid = TRUE;
		} else {
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidConfiguration'), t3lib_FlashMessage::ERROR);
		}

		if ($submittedData['depth'] < 0) {
			$isValid = FALSE;
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidDepth'), t3lib_FlashMessage::ERROR);
		}

		if (!tx_crawler_api::canBeInterpretedAsInteger($submittedData['startPage']) || $submittedData['startPage'] < 0) {
			$isValid = FALSE;
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidStartPage'), t3lib_FlashMessage::ERROR);
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
		$task->depth = intval($submittedData['depth']);
		$task->configuration = $submittedData['configuration'];
		$task->startPage = intval($submittedData['startPage']);
	}
}
