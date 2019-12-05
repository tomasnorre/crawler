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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy;
use AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy;
use AOE\Crawler\QueueExecutor;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class QueueExecutorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validateTypo3InternalGuzzleExecutionIsSelected(): void
    {
        $configuration = [
            'makeDirectRequests' => 0,
            'frontendBasePath' => '/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
        $subject = $this->getAccessibleMock(QueueExecutor::class, ['dummy']);
        $res = $subject->_get('selectedStrategy');
        self::assertEquals(get_class($res), GuzzleExecutionStrategy::class);
    }

    /**
     * @test
     */
    public function validateDirectExecutionIsSelected(): void
    {
        $configuration = [
            'makeDirectRequests' => 1,
            'frontendBasePath' => '/',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
        $subject = $this->getAccessibleMock(QueueExecutor::class, ['dummy']);
        $res = $subject->_get('selectedStrategy');
        self::assertEquals(get_class($res), SubProcessExecutionStrategy::class);
    }

    /**
     * @test
     */
    public function invalidArgumentsReturnErrorInExecuteQueueItem(): void
    {
        $crawlerController = $this->createMock(CrawlerController::class);
        $settings = $this->createMock(ExtensionConfigurationProvider::class);
        $settings->expects($this->once())->method('getExtensionConfiguration')->willReturn([]);
        $subject = new QueueExecutor($settings);
        $result = $subject->executeQueueItem([], $crawlerController);
        self::assertEquals('ERROR', $result);
    }
}
