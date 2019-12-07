<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

use AOE\Crawler\Domain\Model\Reason;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ReasonTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function expectsConstructorToSetPropertiesFromArray(): void
    {
        $propertiesArray = [
            'uid' => 209,
        ];
        $reason = new Reason($propertiesArray);

        self::assertSame(
            $propertiesArray,
            $reason->getRow()
        );
    }

    /**
     * @test
     */
    public function settersWillSetValuesAndGettersWillRetrieveTheValues(): void
    {
        $reason = new Reason([]);

        $reason->setUid(321);
        $reason->setCreationDate('1525456537');
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

        self::assertSame(
            $expectedArray,
            $reason->getRow()
        );
    }
}
