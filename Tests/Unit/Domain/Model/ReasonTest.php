<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Domain\Model\Reason;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ReasonTest
 */
class ReasonTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
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
        $reason->setCreationDate(1525456537);
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
