<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Queue;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Queue\CrawlPriority;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Covers the dispatching without a page, the priority of pages is covered by the functional test.
 */
#[CoversClass(CrawlUrlMessageDispatcher::class)]
class CrawlUrlMessageDispatcherTest extends UnitTestCase
{
    private ?Envelope $dispatchedEnvelope = null;

    private CrawlUrlMessageDispatcher $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][CrawlUrlMessage::class] = 'crawler';

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())->method('dispatch')->willReturnCallback(
            fn (object $message, array $stamps): Envelope => $this->dispatchedEnvelope = new Envelope($message, $stamps)
        );
        $this->subject = new CrawlUrlMessageDispatcher($messageBus);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']);
        parent::tearDown();
    }

    #[Test]
    public function dispatchSendsGivenMessageWithoutDelay(): void
    {
        $message = new CrawlUrlMessage(12, true);

        $this->subject->dispatch($message);

        self::assertNotNull($this->dispatchedEnvelope);
        self::assertSame($message, $this->dispatchedEnvelope->getMessage());
        self::assertNull($this->dispatchedEnvelope->last(DelayStamp::class));
    }

    #[DataProvider('delayDataProvider')]
    #[Test]
    public function dispatchDelaysMessageInMilliseconds(int $delayInSeconds, ?int $expectedDelay): void
    {
        $this->subject->dispatch(new CrawlUrlMessage(12), 0, $delayInSeconds);

        self::assertNotNull($this->dispatchedEnvelope);
        self::assertSame($expectedDelay, $this->dispatchedEnvelope->last(DelayStamp::class)?->getDelay());
    }

    public static function delayDataProvider(): iterable
    {
        yield 'Scheduled in the past' => [-60, null];
        yield 'Scheduled now' => [0, null];
        yield 'Scheduled in one second' => [1, 1_000];
        yield 'Scheduled in one minute' => [60, 60_000];
    }

    #[DataProvider('priorityDataProvider')]
    #[Test]
    public function dispatchSendsMessageToTransportOfPriority(?CrawlPriority $priority, string $expectedTransport): void
    {
        $this->subject->dispatch(new CrawlUrlMessage(12), 0, 0, $priority);

        self::assertNotNull($this->dispatchedEnvelope);
        self::assertSame(
            [$expectedTransport],
            $this->dispatchedEnvelope->last(TransportNamesStamp::class)?->getTransportNames()
        );
    }

    public static function priorityDataProvider(): iterable
    {
        yield 'No priority and no page' => [null, 'crawler'];
        yield 'High' => [CrawlPriority::High, 'crawler_high'];
        yield 'Normal' => [CrawlPriority::Normal, 'crawler'];
        yield 'Low' => [CrawlPriority::Low, 'crawler_low'];
    }

    #[Test]
    public function dispatchSendsMessageToTransportOfPriorityIfRoutingIsGivenAsArray(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][CrawlUrlMessage::class] = ['crawler'];

        $this->subject->dispatch(new CrawlUrlMessage(12), priority: CrawlPriority::Low);

        self::assertNotNull($this->dispatchedEnvelope);
        self::assertSame(
            ['crawler_low'],
            $this->dispatchedEnvelope->last(TransportNamesStamp::class)?->getTransportNames()
        );
    }

    #[DataProvider('changedRoutingDataProvider')]
    #[Test]
    public function dispatchRespectsChangedRouting(string|array|null $routing): void
    {
        if ($routing === null) {
            unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][CrawlUrlMessage::class]);
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][CrawlUrlMessage::class] = $routing;
        }

        $this->subject->dispatch(new CrawlUrlMessage(12), priority: CrawlPriority::High);

        self::assertNotNull($this->dispatchedEnvelope);
        self::assertNull($this->dispatchedEnvelope->last(TransportNamesStamp::class));
    }

    public static function changedRoutingDataProvider(): iterable
    {
        yield 'No routing' => [null];
        yield 'Synchronous transport' => ['default'];
        yield 'Other transport' => ['async'];
        yield 'Additional transport' => [['crawler', 'async']];
    }
}
