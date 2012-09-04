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
class tx_crawler_scheduler_imAdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * render additional information fields within the scheduler backend
	 *
	 * @see interfaces/tx_scheduler_AdditionalFieldProvider#getAdditionalFields($taskInfo, $task, $schedulerModule)
	 * @param array $taskInfo
	 * @param $task
	 * @param tx_scheduler_Module $schedulerModule
	 * @return array
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
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
				$task->startPage = 0;
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
		$fieldID = 'task_startPage';
		$fieldCode  = '<input name="tx_scheduler[startPage]" type="text" id="' . $fieldID . '" value="' . $task->startPage . '" />';
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.startPage'
		);

			// input for depth
		$fieldID = 'task_depth';
		$fieldValueArray = array (
		    '0'  => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_0'),
		    '1'  => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_1'),
		    '2'  => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_2'),
		    '3'  => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_3'),
		    '4'  => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_4'),
		    '99' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_infi'),
		);
		$fieldCode  = '<select name="tx_scheduler[depth]" id="' . $fieldID . '">';

		foreach ($fieldValueArray as $key => $label) {
			$fieldCode .= "\t" . '<option value="' . $key . '"' . (($key == $task->depth) ? ' selected="selected"' : '') . '>' . $label . '</option>';
		}

		$fieldCode .= '</select>';
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.depth'
		);

			// combobox for configuration records
		$recordsArray = $this->getCrawlerConfigurationRecords();
		$fieldID = 'task_configuration';
		$fieldCode  = '<select name="tx_scheduler[configuration][]" multiple="multiple" id="' . $fieldID . '">';
		$fieldCode .= "\t" . '<option value=""></option>';
		for ($i = 0; $i < count($recordsArray); $i++) {		
			$fieldCode .= "\t" . '<option '. $this->getSelectedState($task->configuration, $recordsArray[$i]['name']) . 'value="' . $recordsArray[$i]['name'] . '">' . $recordsArray[$i]['name'] . '</option>';
		}
		$fieldCode .= '</select>';

		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:crawler/locallang_db.xml:crawler_im.conf'
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
			if (strcmp($configurationArray[$i],$currentValue) === 0) {
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
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $schedulerModule
	 *
	 * @internal param \tx_scheduler_module1 $parentObject : reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		$isValid = false;

			//!TODO add validation to validate the $submittedData['configuration'] wich is normally a comma seperated string
		if ( is_array($submittedData['configuration']) ) {
			$isValid = true;
		} else {
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidConfiguration'), t3lib_FlashMessage::ERROR);
		}

		if ( $submittedData['depth'] < 0 ) {
			$isValid = false;
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidDepth'), t3lib_FlashMessage::ERROR);
		}

		if ( !tx_crawler_api::canBeInterpretedAsInteger($submittedData['startPage']) || $submittedData['startPage'] < 0 ) {
			$isValid = false;
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/locallang_db.xml:crawler_im.invalidStartPage'), t3lib_FlashMessage::ERROR);
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

		$task->depth         = intval($submittedData['depth']);
		$task->configuration = $submittedData['configuration'];
		$task->startPage     = intval($submittedData['startPage']);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_scheduler_imAdditionalFieldProvider.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_scheduler_imAdditionalFieldProvider.php']);
}

?>
