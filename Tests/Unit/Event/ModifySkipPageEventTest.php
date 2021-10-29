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

namespace AOE\Crawler\Tests\Unit\Event;

use AOE\Crawler\Event\ModifySkipPageEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * @covers \AOE\Crawler\Event\ModifySkipPageEvent
 */
class ModifySkipPageEventTest extends UnitTestCase
{
    private ModifySkipPageEvent $subject;

    protected function setUp(): void
    {
        $this->subject = new ModifySkipPageEvent(['dummy' => 'array']);
    }

    /**
     * @test
     */
    public function defaultValueTest(): void
    {
        self::assertFalse($this->subject->isSkipped());
        self::assertEquals(
            ['dummy' => 'array'],
            $this->subject->getPageRow()
        );
    }

    /**
     * @test
     */
    public function setterTest(): void
    {
        self::assertFalse($this->subject->isSkipped());
        $this->subject->setSkipped(true);
        self::assertTrue($this->subject->isSkipped());
    }
}
