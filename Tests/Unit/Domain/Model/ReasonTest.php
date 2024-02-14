<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

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

use AOE\Crawler\Domain\Model\Reason;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Domain\Model\Reason::class)]
class ReasonTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function expectsConstructorToSetPropertiesFromArray(): void
    {
        $propertiesArray = [
            'uid' => 209,
        ];
        $reason = new Reason($propertiesArray);

        self::assertSame($propertiesArray, $reason->getRow());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settersWillSetValuesAndGettersWillRetrieveTheValues(): void
    {
        $reason = new Reason([]);

        $reason->setUid(321);
        $reason->setCreationDate(1_525_456_537);
        $reason->setBackendUserId(2);
        $reason->setReason(Reason::REASON_DEFAULT);
        $reason->setDetailText('This is the detail text');
        $reason->setQueueEntryUid(302);

        $expectedArray = [
            'uid' => $reason->getUid(),
            'crdate' => $reason->getCreationDate(),
            'cruser_id' => $reason->getBackendUserId(),
            'reason' => $reason->getReason(),
            'detail_text' => $reason->getDetailText(),
            'queue_entry_uid' => $reason->getQueueEntryUid(),
        ];

        self::assertSame($expectedArray, $reason->getRow());
    }
}
