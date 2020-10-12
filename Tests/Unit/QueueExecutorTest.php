<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit;

/*
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
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\QueueExecutor;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QueueExecutorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function invalidArgumentsReturnErrorInExecuteQueueItem(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];

        $crawlerController = $this->createMock(CrawlerController::class);
        $crawlStrategyFactory = GeneralUtility::makeInstance(CrawlStrategyFactory::class);
        $subject = new QueueExecutor($crawlStrategyFactory);
        $result = $subject->executeQueueItem([], $crawlerController);
        self::assertEquals('ERROR', $result);
    }
}
