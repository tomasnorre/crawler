<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\ContextMenu;

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

use AOE\Crawler\ContextMenu\ItemProvider;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Class ItemProviderTest
 * @covers \AOE\Crawler\ContextMenu\ItemProvider
 */
class ItemProviderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canHandleTxCrawlerConfigurationTable(): void
    {
        $subject = new ItemProvider('tx_crawler_configuration', 'identifier');
        self::assertTrue(
            $subject->canHandle()
        );
    }

    /**
     * @test
     */
    public function cannotHandleTxCrawlerQueueTable(): void
    {
        $subject = new ItemProvider('tx_crawler_queue', 'identifier');
        self::assertFalse(
            $subject->canHandle()
        );
    }
}
