<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Value;

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

use AOE\Crawler\Value\CrawlAction;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class CrawlActionTest extends UnitTestCase
{

    const VALID_VALUE = 'start';

    /**
     * @test
     */
    public function toStringWithValidValueReturnsOriginalValue(): void
    {
        $crawlAction = new CrawlAction(self::VALID_VALUE, 'Label');
        self::assertEquals(
            self::VALID_VALUE,
            $crawlAction->__toString()
        );

        self::assertEquals(
            'Label',
            $crawlAction->getCrawlActionLabel()
        );
    }

    /**
     * @test
     */
    public function constructorThrowsExpection(): void
    {
        self::expectException(\InvalidArgumentException::class);
        new CrawlAction('INVALID', 'label');
    }
}
