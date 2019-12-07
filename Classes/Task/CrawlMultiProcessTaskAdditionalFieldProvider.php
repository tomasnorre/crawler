<?php

declare(strict_types=1);

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
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class CrawlMultiProcessTaskAdditionalFieldProvider
 *
 * @package AOE\Crawler\Task
 * @codeCoverageIgnore
 */
class CrawlMultiProcessTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param CrawlMultiProcessTask $task
     * @return array
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $additionalFields = [];

        if ($schedulerModule->CMD === 'add') {
            $taskInfo['timeOut'] = $taskInfo['timeOut'] ?: 10000;
        }

        if ($schedulerModule->CMD === 'edit') {
            $taskInfo['timeOut'] = $task->timeOut;
        }

        // input for timeOut
        $fieldId = 'task_timeOut';
        $fieldCode = '<input type="text" name="tx_scheduler[timeOut]" id="' . $fieldId . '" value="' . htmlentities($taskInfo['timeOut']) . '" class="form-control" />';
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_im.timeOut',
        ];

        return $additionalFields;
    }

    /**
     * Validates the additional fields' values
     *
     * @return bool
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $isValid = false;

        if (MathUtility::convertToPositiveInteger($submittedData['timeOut']) > 0) {
            $isValid = true;
        } else {
            $schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:crawler_im.invalidTimeOut'), FlashMessage::ERROR);
        }

        return $isValid;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param CrawlMultiProcessTask|AbstractTask $task
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        $task->timeOut = intval($submittedData['timeOut']);
    }
}
