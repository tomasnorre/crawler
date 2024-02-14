<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Utility;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Utility\MessageUtility::class)]
class MessageUtilityTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var BackendUserAuthentication|null
     */
    private $oldBackendUser;

    private \TYPO3\CMS\Core\Messaging\FlashMessageQueue $flashMessageQueue;

    protected function setUp(): void
    {
        $this->flashMessageQueue = GeneralUtility::makeInstance(
            FlashMessageService::class
        )->getMessageQueueByIdentifier();
        // Done to have the queue cleared to not stack the messages
        $this->flashMessageQueue->clear();

        $this->oldBackendUser = $GLOBALS['BE_USER'] ?? null;
        $backendUserStub = $this->createStub(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserStub;
    }

    protected function tearDown(): void
    {
        if ($this->oldBackendUser) {
            $GLOBALS['BE_USER'] = $this->oldBackendUser;
        } else {
            unset($GLOBALS['BE_USER']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function addNoticeMessage(): void
    {
        $messageText = 'This is a notice message';
        MessageUtility::addNoticeMessage($messageText);

        $messages = $this->getMessages();

        self::assertCount(1, $messages);

        self::assertEquals($messageText, $messages[0]->getMessage());

        self::assertEquals(ContextualFeedbackSeverity::NOTICE->value, $messages[0]->getSeverity()->value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function addErrorMessage(): void
    {
        $messageText = 'This is a error message';
        MessageUtility::addErrorMessage($messageText);

        $messages = $this->getMessages();

        self::assertCount(1, $messages);

        self::assertEquals($messageText, $messages[0]->getMessage());
        self::assertEquals(ContextualFeedbackSeverity::ERROR->value, $messages[0]->getSeverity()->value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function addWarningMessage(): void
    {
        $messageText = 'This is a warning message';
        MessageUtility::addWarningMessage($messageText);

        $messages = $this->getMessages();
        self::assertCount(1, $messages);

        self::assertEquals($messageText, $messages[0]->getMessage());

        self::assertEquals(ContextualFeedbackSeverity::WARNING->value, $messages[0]->getSeverity()->value);
    }

    /**
     * @return FlashMessage[]
     */
    private function getMessages()
    {
        return $this->flashMessageQueue->getAllMessages();
    }
}
