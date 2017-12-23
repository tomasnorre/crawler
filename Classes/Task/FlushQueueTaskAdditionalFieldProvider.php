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

use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class FlushQueueTaskAdditionalFieldProvider
 *
 * @package AOE\Crawler\Task
 * @codeCoverageIgnore
 */
class FlushQueueTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo
     * @param FlushQueueTask $task
     * @param SchedulerModuleController $schedulerModule
     * @return array
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $additionalFields = [];
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
        $fieldCode = '<select name="tx_scheduler[mode]" id="' . $fieldId . '" value="' . htmlentities($taskInfo['mode']) . '" class="form-control">'
            . '<option value="all"' . ($taskInfo['mode'] == 'all' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_flush.modeAll') . '</option>'
            . '<option value="finished"' . ($taskInfo['mode'] == 'finished' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_flush.modeFinished') . '</option>'
            . '<option value="pending"' . ($taskInfo['mode'] == 'pending' ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_flush.modePending') . '</option>'
            . '</select>';

        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_flush.mode'
        ];

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
        return in_array($submittedData['mode'], ['all', 'pending', 'finished']);
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData
     * @param FlushQueueTask|AbstractTask $task
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->mode = $submittedData['mode'];
    }
}
