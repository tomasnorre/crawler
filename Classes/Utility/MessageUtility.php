<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Utility;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
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
        self::addMessage($message, FlashMessage::NOTICE);
    }

    /**
     * Add error message to the user interface.
     */
    public static function addErrorMessage(string $message): void
    {
        self::addMessage($message, FlashMessage::ERROR);
    }

    /**
     * Add error message to the user interface.
     */
    public static function addWarningMessage(string $message): void
    {
        self::addMessage($message, FlashMessage::WARNING);
    }

    /**
     * This method is used to add a message to the internal queue
     *
     * @param string $message the message itself
     * @param int $severity message level (0 = success (default), -1 = info, -2 = notice, 1 = warning, 2 = error)
     */
    private static function addMessage(string $message, int $severity = FlashMessage::OK): void
    {
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            '',
            $severity
        );

        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageService->getMessageQueueByIdentifier()->addMessage($message);
    }
}
