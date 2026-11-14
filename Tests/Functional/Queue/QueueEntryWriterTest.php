<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Queue;

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

use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\Queue\QueueEntryWriter;
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use AOE\Crawler\Tests\Functional\QueueEntryWriterTestTrait;
use AOE\Crawler\Tests\Unit\CrawlStrategy\CallbackObjectForTesting;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(QueueEntryWriter::class)]
class QueueEntryWriterTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;
    use QueueEntryWriterTestTrait;

    protected array $coreExtensionsToLoad = ['seo'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupBackendRequest();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_configuration.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('addUrlDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function addUrl(
        int $id,
        string $url,
        array $subCfg,
        int $tstamp,
        string $configurationHash,
        bool $skipInnerDuplicationCheck,
        array $mockedDuplicateRowResult,
        bool $registerQueueEntriesInternallyOnly,
        bool $expected
    ): void {
        $mockedQueueRepository = $this->getAccessibleMock(
            QueueRepository::class,
            ['getDuplicateQueueItemsIfExists']
        );

        $mockedQueueRepository->method('getDuplicateQueueItemsIfExists')->willReturn($mockedDuplicateRowResult);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($expected ? self::once() : self::never())
            ->method('dispatch')
            ->with(self::isInstanceOf(CrawlUrlMessage::class), self::isArray())
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $subject = $this->createQueueEntryWriter($messageBus, [], $mockedQueueRepository);
        $subject->registerQueueEntriesInternallyOnly = $registerQueueEntriesInternallyOnly;

        self::assertEquals(
            $expected,
            $subject->addUrl($id, $url, $subCfg, $tstamp, $configurationHash, $skipInnerDuplicationCheck)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function addUrlDelaysMessageForEntriesScheduledInTheFuture(): void
    {
        $mockedQueueRepository = $this->getAccessibleMock(QueueRepository::class, ['getDuplicateQueueItemsIfExists']);
        $mockedQueueRepository->method('getDuplicateQueueItemsIfExists')->willReturn([]);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $message, array $stamps): Envelope {
                self::assertInstanceOf(CrawlUrlMessage::class, $message);
                $delayStamps = array_values(
                    array_filter($stamps, static fn (object $stamp): bool => $stamp instanceof DelayStamp)
                );
                self::assertCount(1, $delayStamps);
                self::assertSame(60_000, $delayStamps[0]->getDelay());
                return new Envelope($message, $stamps);
            });

        $subject = $this->createQueueEntryWriter($messageBus, ['getCurrentTime'], $mockedQueueRepository);
        $subject->method('getCurrentTime')->willReturn(1_000_000);

        self::assertTrue($subject->addUrl(1, 'https://example.com/', [
            'key' => 'some-key',
        ], 1_000_060));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function addCallbackStoresQueueEntryAndDispatchesMessage(): void
    {
        $dispatchedMessages = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $message, array $stamps) use (&$dispatchedMessages): Envelope {
                $dispatchedMessages[] = $message;
                return new Envelope($message, $stamps);
            });

        $subject = $this->createQueueEntryWriter($messageBus, ['getCurrentTime']);
        $subject->method('getCurrentTime')->willReturn(1_000_000);
        $subject->setId = 42;

        $queueId = $subject->addCallback(CallbackObjectForTesting::class, [
            'name' => 'Crawler',
        ], 5);

        $queueEntry = GeneralUtility::makeInstance(QueueRepository::class)->getQueueEntriesByQid($queueId, true);
        self::assertIsArray($queueEntry);
        self::assertSame(5, (int) $queueEntry['page_id']);
        self::assertSame(42, (int) $queueEntry['set_id']);
        self::assertSame(1_000_000, (int) $queueEntry['scheduled']);
        self::assertSame([
            'name' => 'Crawler',
            QueueEntryWriter::CALLBACK_PARAMETER => CallbackObjectForTesting::class,
        ], json_decode((string) $queueEntry['parameters'], true));

        self::assertCount(1, $dispatchedMessages);
        self::assertInstanceOf(CrawlUrlMessage::class, $dispatchedMessages[0]);
        self::assertSame($queueId, $dispatchedMessages[0]->queueId);
    }

    public static function addUrlDataProvider(): iterable
    {
        yield 'Queue entry added' => [
            'id' => 0,
            'url' => '',
            'subCfg' => [
                'key' => 'some-key',
                'procInstrFilter' => 'tx_crawler_post',
                'procInstrParams.' => [
                    'action' => true,
                ],
                'userGroups' => '12,14',
            ],
            'tstamp' => 1_563_287_062,
            'configurationHash' => '',
            'skipInnerDuplicationCheck' => false,
            'mockedDuplicateRowResult' => [],
            'registerQueueEntriesInternallyOnly' => false,
            'expected' => true,
        ];
        yield 'Queue entry is NOT added, due to duplication check return not empty array (mocked)' => [
            'id' => 0,
            'url' => '',
            'subCfg' => [
                'key' => 'some-key',
            ],
            'tstamp' => 1_563_287_062,
            'configurationHash' => '',
            'skipInnerDuplicationCheck' => false,
            'mockedDuplicateRowResult' => [
                'duplicate-exists' => true,
            ],
            'registerQueueEntriesInternallyOnly' => false,
            'expected' => false,
        ];
        yield 'Queue entry is added, due to duplication is ignored' => [
            'id' => 0,
            'url' => '',
            'subCfg' => [
                'key' => 'some-key',
            ],
            'tstamp' => 1_563_287_062,
            'configurationHash' => '',
            'skipInnerDuplicationCheck' => true,
            'mockedDuplicateRowResult' => [
                'duplicate-exists' => true,
            ],
            'registerQueueEntriesInternallyOnly' => false,
            'expected' => true,
        ];
        yield 'Queue entry is NOT added, due to registerQueueEntriesInternalOnly' => [
            'id' => 0,
            'url' => '',
            'subCfg' => [
                'key' => 'some-key',
            ],
            'tstamp' => 1_563_287_062,
            'configurationHash' => '',
            'skipInnerDuplicationCheck' => true,
            'mockedDuplicateRowResult' => [
                'duplicate-exists' => true,
            ],
            'registerQueueEntriesInternallyOnly' => true,
            'expected' => false,
        ];
    }
}
