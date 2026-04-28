<?php

declare(strict_types=1);

namespace AOE\Crawler\Utility;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class MessageUtility
{
    /**
     * Add notice message to the user interface.
     */
    public static function addNoticeMessage(string $message): void
    {
        self::addMessage($message, ContextualFeedbackSeverity::NOTICE);
    }

    /**
     * Add error message to the user interface.
     */
    public static function addErrorMessage(string $message): void
    {
        self::addMessage($message, ContextualFeedbackSeverity::ERROR);
    }

    /**
     * Add error message to the user interface.
     */
    public static function addWarningMessage(string $message): void
    {
        self::addMessage($message, ContextualFeedbackSeverity::WARNING);
    }

    /**
     * This method is used to add a message to the internal queue
     *
     * @param string $message the message itself
     * @param ContextualFeedbackSeverity $severity message level (0 = success (default), -1 = info, -2 = notice, 1 = warning, 2 = error)
     */
    private static function addMessage(
        string $message,
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK
    ): void {
        $message = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageService->getMessageQueueByIdentifier()->addMessage($message);
    }
}
