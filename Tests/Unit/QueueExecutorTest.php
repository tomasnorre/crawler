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
use AOE\Crawler\CrawlStrategy\CallbackExecutionStrategy;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Tests\Unit\CrawlStrategy\CallbackObjectForTesting;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(QueueExecutor::class)]
#[CoversClass(ExtensionConfigurationProvider::class)]
#[CoversClass(CallbackExecutionStrategy::class)]
#[CoversClass(CrawlStrategyFactory::class)]
class QueueExecutorTest extends UnitTestCase
{
    use ProphecyTrait;

    protected QueueExecutor $queueExecutor;

    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [];
        $crawlStrategyFactory = GeneralUtility::makeInstance(CrawlStrategyFactory::class);

        $callbacks = $this->createStub(ContainerInterface::class);
        $callbacks->method('has')->willReturn(false);

        $this->queueExecutor = new QueueExecutor(
            $crawlStrategyFactory,
            $this->prophesize(EventDispatcher::class)->reveal(),
            new CallbackExecutionStrategy($callbacks),
        );
    }

    #[DataProvider('invalidArgumentsReturnErrorInExecuteQueueItemDataProvider')]
    #[Test]
    public function invalidArgumentsReturnErrorInExecuteQueueItem(array $queueItem): void
    {
        $result = $this->queueExecutor->executeQueueItem($queueItem);
        self::assertEquals('ERROR', $result);
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

        self::assertSame([
            'content' => json_encode('Hi, it works! Crawler'),
        ], $result);
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
