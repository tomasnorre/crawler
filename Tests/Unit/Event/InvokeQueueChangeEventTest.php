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

use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Event\InvokeQueueChangeEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * @covers \AOE\Crawler\Event\InvokeQueueChangeEvent
 */
class InvokeQueueChangeEventTest extends UnitTestCase
{
    private InvokeQueueChangeEvent $subject;

    protected function setUp(): void
    {
        $reason = new Reason();
        $this->subject = new InvokeQueueChangeEvent($reason);
    }

    /**
     * @test
     */
    public function defaultValueTest(): void
    {
        self::assertInstanceOf(Reason::class, $this->subject->getReason());
    }
}
