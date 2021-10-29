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

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

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
