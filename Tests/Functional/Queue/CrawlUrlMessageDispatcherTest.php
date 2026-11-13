<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Queue;

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CrawlUrlMessageDispatcherTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['seo'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    private ?Envelope $dispatchedEnvelope = null;

    private CrawlUrlMessageDispatcher $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');

        $messageBus = $this->createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(
            fn (object $message, array $stamps): Envelope => $this->dispatchedEnvelope = new Envelope($message, $stamps)
        );
        $this->subject = new CrawlUrlMessageDispatcher($messageBus);
    }

    #[DataProvider('pagePriorityDataProvider')]
    #[Test]
    public function dispatchSendsMessageToTransportOfPagePriority(int $pageId, string $expectedTransport): void
    {
        $this->subject->dispatch(new CrawlUrlMessage(1), $pageId);

        self::assertSame([$expectedTransport], $this->getTransportNames());
    }

    public static function pagePriorityDataProvider(): iterable
    {
        yield 'Page with sitemap_priority 1.0' => [1, 'crawler_high'];
        yield 'Page with sitemap_priority 0.5' => [3, 'crawler'];
        yield 'Page with sitemap_priority 0.2' => [2, 'crawler_low'];
        yield 'No page' => [0, 'crawler'];
        yield 'Unknown page' => [999, 'crawler'];
    }

    #[Test]
    public function dispatchUsesGivenPriorityOverPagePriority(): void
    {
        $this->subject->dispatch(new CrawlUrlMessage(1), 2, priority: CrawlPriority::High);

        self::assertSame(['crawler_high'], $this->getTransportNames());
    }

    #[Test]
    public function dispatchRespectsChangedRouting(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][CrawlUrlMessage::class] = 'default';

        $this->subject->dispatch(new CrawlUrlMessage(1), 1);

        self::assertNotNull($this->dispatchedEnvelope);
        self::assertNull($this->dispatchedEnvelope->last(TransportNamesStamp::class));
    }

    #[Test]
    public function dispatchDelaysMessage(): void
    {
        $this->subject->dispatch(new CrawlUrlMessage(1), 1, 30);

        self::assertNotNull($this->dispatchedEnvelope);
        self::assertSame(30_000, $this->dispatchedEnvelope->last(DelayStamp::class)?->getDelay());
    }

    private function getTransportNames(): ?array
    {
        self::assertNotNull($this->dispatchedEnvelope);
        return $this->dispatchedEnvelope->last(TransportNamesStamp::class)?->getTransportNames();
    }
}
