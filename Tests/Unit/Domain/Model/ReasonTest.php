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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Domain\Model\Reason::class)]
class ReasonTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorSetsPropertiesFromRow(): void
    {
        $reason = new Reason([
            'uid' => 209,
            'crdate' => 1_525_456_537,
            'cruser_id' => 2,
            'reason' => Reason::REASON_GUI_SUBMIT,
            'detail_text' => 'This is the detail text',
            'queue_entry_uid' => 302,
        ]);

        self::assertSame(209, $reason->getUid());
        self::assertSame(1_525_456_537, $reason->getCreationDate());
        self::assertSame(2, $reason->getBackendUserId());
        self::assertSame(Reason::REASON_GUI_SUBMIT, $reason->getReason());
        self::assertSame('This is the detail text', $reason->getDetailText());
        self::assertSame(302, $reason->getQueueEntryUid());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorCastsDatabaseValues(): void
    {
        $reason = new Reason([
            'uid' => '209',
            'crdate' => '1525456537',
            'cruser_id' => '2',
            'reason' => 42,
            'detail_text' => 1.5,
            'queue_entry_uid' => '302',
        ]);

        self::assertSame(209, $reason->getUid());
        self::assertSame(1_525_456_537, $reason->getCreationDate());
        self::assertSame(2, $reason->getBackendUserId());
        self::assertSame('42', $reason->getReason());
        self::assertSame('1.5', $reason->getDetailText());
        self::assertSame(302, $reason->getQueueEntryUid());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorFallsBackToDefaultsForMissingValues(): void
    {
        $reason = new Reason();

        self::assertSame(0, $reason->getUid());
        self::assertSame(0, $reason->getCreationDate());
        self::assertSame(0, $reason->getBackendUserId());
        self::assertSame('', $reason->getReason());
        self::assertSame('', $reason->getDetailText());
        self::assertSame(0, $reason->getQueueEntryUid());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructorFallsBackToDefaultsForInvalidValues(): void
    {
        $reason = new Reason([
            'uid' => 'abc',
            'crdate' => null,
            'cruser_id' => [2],
            'reason' => ['reason'],
            'detail_text' => new \stdClass(),
            'queue_entry_uid' => true,
        ]);

        self::assertSame(0, $reason->getUid());
        self::assertSame(0, $reason->getCreationDate());
        self::assertSame(0, $reason->getBackendUserId());
        self::assertSame('', $reason->getReason());
        self::assertSame('', $reason->getDetailText());
        self::assertSame(0, $reason->getQueueEntryUid());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function settersWillSetValuesAndGettersWillRetrieveTheValues(): void
    {
        $reason = new Reason();

        $reason->setUid(321);
        $reason->setCreationDate(1_525_456_537);
        $reason->setBackendUserId(2);
        $reason->setReason(Reason::REASON_DEFAULT);
        $reason->setDetailText('This is the detail text');
        $reason->setQueueEntryUid(302);

        self::assertSame(321, $reason->getUid());
        self::assertSame(1_525_456_537, $reason->getCreationDate());
        self::assertSame(2, $reason->getBackendUserId());
        self::assertSame(Reason::REASON_DEFAULT, $reason->getReason());
        self::assertSame('This is the detail text', $reason->getDetailText());
        self::assertSame(302, $reason->getQueueEntryUid());
    }
}
