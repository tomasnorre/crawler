<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Utility;

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

use AOE\Crawler\Utility\MessageUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MessageUtilityTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function addNoticeMessage(): void
    {
        $messageText = 'This is a notice message';
        MessageUtility::addNoticeMessage($messageText);

        $messages = self::getMessages();
        self::assertEquals(
            $messageText,
            $messages[0]->getMessage()
        );

        self::assertEquals(
            FlashMessage::NOTICE,
            $messages[0]->getSeverity()
        );
    }

    /**
     * @test
     */
    public function addErrorMessage(): void
    {
        $messageText = 'This is a error message';
        MessageUtility::addErrorMessage($messageText);

        $messages = self::getMessages();
        self::assertEquals(
            $messageText,
            $messages[0]->getMessage()
        );

        self::assertEquals(
            FlashMessage::ERROR,
            $messages[0]->getSeverity()
        );
    }

    /**
     * @test
     */
    public function addWarningMessage(): void
    {
        $messageText = 'This is a warning message';
        MessageUtility::addWarningMessage($messageText);

        $messages = self::getMessages();
        self::assertEquals(
            $messageText,
            $messages[0]->getMessage()
        );

        self::assertEquals(
            FlashMessage::WARNING,
            $messages[0]->getSeverity()
        );
    }

    private function getMessages()
    {
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier();
        return $flashMessageQueue->getAllMessages();
    }
}
