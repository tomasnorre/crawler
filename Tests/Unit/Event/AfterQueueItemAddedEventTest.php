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

use AOE\Crawler\Event\AfterQueueItemAddedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Event\AfterQueueItemAddedEvent::class)]
class AfterQueueItemAddedEventTest extends UnitTestCase
{
    private AfterQueueItemAddedEvent $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $queueId = 'qwerty';
        $fieldArray = ['field' => 'array'];
        $this->subject = new AfterQueueItemAddedEvent($queueId, $fieldArray);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function defaultValuesTest(): void
    {
        self::assertEquals('qwerty', $this->subject->getQueueId());

        self::assertEquals(['field' => 'array'], $this->subject->getFieldArray());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setterTest(): void
    {
        $differentArray = ['different' => 'array'];
        $this->subject->setFieldArray($differentArray);
        self::assertEquals($differentArray, $this->subject->getFieldArray());
    }
}
