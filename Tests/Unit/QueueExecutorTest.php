<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit;

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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Tests\Unit\CrawlStrategy\CallbackObjectForTesting;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\QueueExecutor::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Configuration\ExtensionConfigurationProvider::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Converter\JsonCompatibilityConverter::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\CrawlStrategy\CrawlStrategyFactory::class)]
class QueueExecutorTest extends UnitTestCase
{
    use ProphecyTrait;

    protected \AOE\Crawler\QueueExecutor $queueExecutor;

    /**
     * @var CrawlerController
     */
    protected $mockedCrawlerController;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];
        $this->mockedCrawlerController = $this->createMock(CrawlerController::class);
        $crawlStrategyFactory = GeneralUtility::makeInstance(CrawlStrategyFactory::class);

        $this->queueExecutor = new QueueExecutor(
            $crawlStrategyFactory,
            $this->prophesize(EventDispatcher::class)->reveal()
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidArgumentsReturnErrorInExecuteQueueItemDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function invalidArgumentsReturnErrorInExecuteQueueItem(array $queueItem): void
    {
        $result = $this->queueExecutor->executeQueueItem($queueItem, $this->mockedCrawlerController);
        self::assertEquals('ERROR', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function executeQueueItemCallback(): void
    {
        $queueItem = [
            'parameters' => serialize(['_CALLBACKOBJ' => CallbackObjectForTesting::class]),
        ];
        $result = $this->queueExecutor->executeQueueItem($queueItem, $this->mockedCrawlerController);

        self::assertIsArray($result);
        self::assertArrayHasKey('content', $result);
        self::assertStringContainsString('Hi, it works!', $result['content']);
    }

    public static function invalidArgumentsReturnErrorInExecuteQueueItemDataProvider(): iterable
    {
        yield 'No parameters set' => [
            'queueItem' => [],
        ];

        yield 'Parameters set, but empty' => [
            'queueItem' => [
                'parameters' => '',
            ],
        ];
        yield 'Parameters set, can be converted' => [
            'queueItem' => [
                'parameters' => serialize('Simple string'),
            ],
        ];
        yield 'Parameters set, cannot be converted' => [
            'queueItem' => [
                'parameters' => 'A simple string not encoded',
            ],
        ];
    }
}
