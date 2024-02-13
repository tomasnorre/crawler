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

use AOE\Crawler\Event\AfterUrlAddedToQueueEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * @covers \AOE\Crawler\Event\AfterUrlAddedToQueueEvent
 */
class AfterUrlAddedToQueueEventTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    private AfterUrlAddedToQueueEvent $subject;

    protected function setUp(): void
    {
        $uid = 'qwerty';
        $fieldArray = ['field' => 'array'];
        $this->subject = new AfterUrlAddedToQueueEvent($uid, $fieldArray);
    }

    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;
    }

    /**
     * @test
     */
    public function defaultValueTest(): void
    {
        self::assertEquals('qwerty', $this->subject->getUid());

        self::assertEquals(['field' => 'array'], $this->subject->getFieldArray());
    }
}
