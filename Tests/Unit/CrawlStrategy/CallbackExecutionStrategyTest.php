<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CallbackExecutionStrategyTest extends UnitTestCase
{
    /**
     * @test
     * @covers \AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy
     */
    public function callbackExecutionStrategyTest(): void
    {
        $crawlerController = $this->createPartialMock(CrawlerController::class, []);

        self::assertEquals(
            'Hi, it works!',
            GeneralUtility::makeInstance(CallbackExecutionStrategy::class)
                ->fetchByCallback(CallbackObjectForTesting::class, [], $crawlerController)
        );
    }
}
