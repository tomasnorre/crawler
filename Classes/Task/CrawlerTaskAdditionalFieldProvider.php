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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class CrawlerTaskAdditionalFieldProvider
 *
 * @package AOE\Crawler\Task
 */
class CrawlerTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo
     * @param AbstractTask $task
     * @param SchedulerModuleController $schedulerModule
     * @return array
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
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
        $fieldId = 'task_sleepTime';
        $fieldCode = '<input type="text" name="tx_scheduler[sleepTime]" id="' . $fieldId . '" value="' . htmlentities($taskInfo['sleepTime']) . '" class="form-control" />';
        $additionalFields[$fieldId] = array(
            'code' => $fieldCode,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_im.sleepTime'
        );
        // input for sleepAfterFinish
        $fieldId = 'task_sleepAfterFinish';
        $fieldCode = '<input type="text" name="tx_scheduler[sleepAfterFinish]" id="' . $fieldId . '" value="' . htmlentities($taskInfo['sleepAfterFinish']) . '" class="form-control" />';
        $additionalFields[$fieldId] = array(
            'code' => $fieldCode,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_im.sleepAfterFinish'
        );
        // input for countInARun
        $fieldId = 'task_countInARun';
        $fieldCode = '<input type="text" name="tx_scheduler[countInARun]" id="' . $fieldId . '" value="' . htmlentities($taskInfo['countInARun']) . '" class="form-control" />';
        $additionalFields[$fieldId] = array(
            'code' => $fieldCode,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_im.countInARun'
        );

        return $additionalFields;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData
     * @param SchedulerModuleController $schedulerModule
     * @return bool
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $isValid = false;

        if (\tx_crawler_api::convertToPositiveInteger($submittedData['sleepTime']) > 0) {
            $isValid = true;
        } else {
            $schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_im.invalidSleepTime'), FlashMessage::ERROR);
        }

        if (\tx_crawler_api::convertToPositiveInteger($submittedData['sleepAfterFinish']) === 0) {
            $isValid = false;
            $schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_im.invalidSleepAfterFinish'), FlashMessage::ERROR);
        }

        if (\tx_crawler_api::convertToPositiveInteger($submittedData['countInARun']) === 0) {
            $isValid = false;
            $schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_im.invalidCountInARun'), FlashMessage::ERROR);
        }

        return $isValid;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData
     * @param AbstractTask $task
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task) {
        $task->sleepTime = intval($submittedData['sleepTime']);
        $task->sleepAfterFinish = intval($submittedData['sleepAfterFinish']);
        $task->countInARun = intval($submittedData['countInARun']);
    }

}