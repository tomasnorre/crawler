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
use AOE\Crawler\Configuration\ExtensionSettings;
use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy;
use AOE\Crawler\CrawlStrategy\Interface\CrawlStrategyInterface;
use AOE\Crawler\Event\AfterUrlCrawledEvent;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Tests\Unit\CrawlStrategy\CallbackObjectForTesting;
use AOE\Crawler\Value\CrawlResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(QueueExecutor::class)]
#[CoversClass(ExtensionConfigurationProvider::class)]
#[CoversClass(CallbackExecutionStrategy::class)]
#[CoversClass(CrawlStrategyFactory::class)]
class QueueExecutorTest extends UnitTestCase
{
    protected QueueExecutor $queueExecutor;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $crawlStrategyFactory = $this->createCrawlStrategyFactory(new GuzzleExecutionStrategy());

        $callbacks = $this->createStub(ContainerInterface::class);
        $callbacks->method('has')->willReturn(false);

        $this->queueExecutor = new QueueExecutor(
            $crawlStrategyFactory,
            $this->createStub(EventDispatcher::class),
            new CallbackExecutionStrategy($callbacks),
        );
    }

    #[DataProvider('invalidArgumentsReturnErrorInExecuteQueueItemDataProvider')]
    #[Test]
    public function invalidArgumentsReturnErrorInExecuteQueueItem(array $queueItem): void
    {
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects(self::never())->method('dispatch');
        $crawlStrategy = $this->createMock(CrawlStrategyInterface::class);
        $crawlStrategy->expects(self::never())->method('fetchUrlContents');

        $result = $this->createQueueExecutor($crawlStrategy, $eventDispatcher)->executeQueueItem($queueItem);

        self::assertEquals(CrawlResult::error(QueueExecutor::INVALID_PARAMETERS_MESSAGE), $result);
    }

    #[Test]
    public function executeQueueItemCallback(): void
    {
        $queueItem = [
            'parameters' => json_encode([
                'name' => 'Crawler',
                QueueEntryWriter::CALLBACK_PARAMETER => CallbackObjectForTesting::class,
            ]),
        ];

        $result = $this->queueExecutor->executeQueueItem($queueItem);

        self::assertSame('Hi, it works! Crawler', $result->getContent());
    }

    #[Test]
    public function executeQueueItemFetchesUrlWithCrawlStrategyAndDispatchesEvent(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'encryption-key';
        $crawlResult = CrawlResult::fromContent([
            'vars' => [
                'id' => 5,
            ],
        ]);
        $crawlStrategy = $this->createMock(GuzzleExecutionStrategy::class);
        $crawlStrategy->expects(self::once())->method('fetchUrlContents')
            ->with(new Uri('https://example.com/'), '12:' . md5('12|34|encryption-key'))
            ->willReturn($crawlResult);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects(self::once())->method('dispatch')->with(self::callback(
            static fn (AfterUrlCrawledEvent $event): bool => $event->getUrl() === 'https://example.com/'
                && $event->getResult() === $crawlResult
        ))->willReturnArgument(0);

        $result = $this->createQueueExecutor($crawlStrategy, $eventDispatcher)->executeQueueItem([
            'qid' => 12,
            'set_id' => 34,
            'parameters' => '{"url":"https:\/\/example.com\/"}',
        ]);

        self::assertSame($crawlResult, $result);
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
        yield 'Parameters set, but an empty JSON array' => [
            'queueItem' => [
                'parameters' => '[]',
            ],
        ];
        yield 'Parameters set, cannot be converted' => [
            'queueItem' => [
                'parameters' => 'A simple string not encoded',
            ],
        ];
    }

    private function createQueueExecutor(
        CrawlStrategyInterface $crawlStrategy,
        EventDispatcher $eventDispatcher
    ): QueueExecutor {
        return new QueueExecutor(
            $this->createCrawlStrategyFactory($crawlStrategy),
            $eventDispatcher,
            new CallbackExecutionStrategy($this->createStub(ContainerInterface::class)),
        );
    }

    /**
     * The factory creates the Guzzle strategy, as direct requests are not enabled
     */
    private function createCrawlStrategyFactory(CrawlStrategyInterface $crawlStrategy): CrawlStrategyFactory
    {
        $extensionConfigurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->method('getExtensionSettings')->willReturn(new ExtensionSettings());
        $strategies = $this->createStub(ContainerInterface::class);
        $strategies->method('get')->willReturnMap([[GuzzleExecutionStrategy::class, $crawlStrategy]]);

        return new CrawlStrategyFactory($extensionConfigurationProvider, $strategies);
    }
}
