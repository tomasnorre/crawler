<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Event;

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

use AOE\Crawler\Event\BeforeQueueItemAddedEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * @covers \AOE\Crawler\Event\BeforeQueueItemAddedEvent
 */
class BeforeQueueItemAddedEventTest extends UnitTestCase
{
    private BeforeQueueItemAddedEvent $subject;

    protected function setUp(): void
    {
        $queueId = 1234;
        $queueRecord = ['simple' => 'array'];
        $this->subject = new BeforeQueueItemAddedEvent($queueId, $queueRecord);
    }

    /**
     * @test
     */
    public function defaultValueTest(): void
    {
        self::assertEquals(1234, $this->subject->getQueueId());

        self::assertEquals(['simple' => 'array'], $this->subject->getQueueRecord());
    }

    /**
     * @test
     */
    public function setterTest(): void
    {
        $differentArray = ['different' => 'array'];
        $this->subject->setQueueRecord($differentArray);

        self::assertEquals($differentArray, $this->subject->getQueueRecord());
    }
}
