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

use AOE\Crawler\Event\ModifySkipPageEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Event\ModifySkipPageEvent::class)]
class ModifySkipPageEventTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    private ModifySkipPageEvent $subject;

    protected function setUp(): void
    {
        $this->subject = new ModifySkipPageEvent(['dummy' => 'array']);
    }

    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function defaultValueTest(): void
    {
        self::assertFalse($this->subject->isSkipped());
        self::assertEquals(['dummy' => 'array'], $this->subject->getPageRow());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function setterTest(): void
    {
        $skippedReason = 'Skipped as in exclude list';
        self::assertFalse($this->subject->isSkipped());
        $this->subject->setSkipped($skippedReason);
        self::assertEquals($skippedReason, $this->subject->isSkipped());
    }
}
