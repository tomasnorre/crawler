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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Tests\Unit\CrawlStrategy\CallbackObjectForTesting;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[CoversClass(QueueExecutor::class)]
#[CoversClass(ExtensionConfigurationProvider::class)]
#[CoversClass(JsonCompatibilityConverter::class)]
#[CoversClass(CallbackExecutionStrategy::class)]
#[CoversClass(CrawlStrategyFactory::class)]
class QueueExecutorTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    use ProphecyTrait;

    protected QueueExecutor $queueExecutor;

    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;
    }

    /**
     * @var CrawlerController
     */
    protected $mockedCrawlerController;

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];
        $this->mockedCrawlerController = $this->createMock(CrawlerController::class);
        $crawlStrategyFactory = GeneralUtility::makeInstance(CrawlStrategyFactory::class);

        $this->queueExecutor = new QueueExecutor(
            $crawlStrategyFactory,
            $this->prophesize(EventDispatcher::class)->reveal()
        );
    }

    #[DataProvider('invalidArgumentsReturnErrorInExecuteQueueItemDataProvider')]
    #[Test]
    public function invalidArgumentsReturnErrorInExecuteQueueItem(array $queueItem): void
    {
        $result = $this->queueExecutor->executeQueueItem($queueItem, $this->mockedCrawlerController);
        self::assertEquals('ERROR', $result);
    }

    #[Test]
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
