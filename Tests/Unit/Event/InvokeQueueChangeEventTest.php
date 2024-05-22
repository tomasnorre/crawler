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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Domain\Model\Reason::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Event\InvokeQueueChangeEvent::class)]
class InvokeQueueChangeEventTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    private InvokeQueueChangeEvent $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $reason = new Reason();
        $reason->setReason(Reason::REASON_CLI_SUBMIT);
        $reason->setDetailText('More detailed text');
        $this->subject = new InvokeQueueChangeEvent($reason);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function defaultValueTest(): void
    {
        self::assertEquals(Reason::REASON_CLI_SUBMIT, $this->subject->getReasonText());

        self::assertEquals('More detailed text', $this->subject->getReasonDetailedText());
    }
}
