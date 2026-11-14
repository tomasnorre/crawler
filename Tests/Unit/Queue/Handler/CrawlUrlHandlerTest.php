<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Queue\Handler;

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

use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\AfterQueueItemAddedEvent;
use AOE\Crawler\Event\BeforeQueueItemAddedEvent;
use AOE\Crawler\Queue\Handler\CrawlUrlHandler;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\QueueExecutor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(CrawlUrlHandler::class)]
#[CoversClass(CrawlUrlMessage::class)]
class CrawlUrlHandlerTest extends UnitTestCase
{
    private QueueRepository&MockObject $queueRepository;
    private QueueExecutor&MockObject $queueExecutor;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CrawlUrlHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueRepository = $this->createMock(QueueRepository::class);
        $this->queueExecutor = $this->createMock(QueueExecutor::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->subject = new CrawlUrlHandler($this->queueRepository, $this->queueExecutor, $this->eventDispatcher);
    }

    #[Test]
    public function doesNothingIfQueueRecordIsNotFoundOrAlreadyCrawled(): void
    {
        $this->queueRepository->method('getQueueEntriesByQid')->with(12, false)->willReturn(false);
        $this->queueExecutor->expects(self::never())->method('executeQueueItem');
        $this->queueRepository->expects(self::never())->method('update');
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        self::assertNull(($this->subject)(new CrawlUrlMessage(12)));
    }

    #[Test]
    public function crawlsQueueRecordAndStoresResult(): void
    {
        $queueRecord = [
            'qid' => 12,
            'parameters' => '{"url":"https://example.com/"}',
        ];
        $modifiedQueueRecord = $queueRecord + [
            'modified' => true,
        ];
        $result = [
            'content' => '{"vars":{"status":"200 OK"}}',
        ];

        $this->queueRepository->method('getQueueEntriesByQid')->with(12, true)->willReturn($queueRecord);
        $this->eventDispatcher->expects(self::exactly(2))->method('dispatch')->willReturnCallback(
            static function (object $event) use ($modifiedQueueRecord): object {
                if ($event instanceof BeforeQueueItemAddedEvent) {
                    $event->setQueueRecord($modifiedQueueRecord);
                }
                if ($event instanceof AfterQueueItemAddedEvent) {
                    $event->setFieldArray($event->getFieldArray() + [
                        'added_by_listener' => 1,
                    ]);
                }
                return $event;
            }
        );
        $this->queueExecutor->expects(self::once())->method('executeQueueItem')
            ->with($modifiedQueueRecord)
            ->willReturn($result);

        $updates = [];
        $this->queueRepository->expects(self::exactly(2))->method('update')->willReturnCallback(
            static function (int $queueId, array $fieldArray) use (&$updates): void {
                $updates[] = [$queueId, $fieldArray];
            }
        );

        self::assertSame($result, ($this->subject)(new CrawlUrlMessage(12, true)));

        // First the record is locked by setting exec_time, then the result is stored.
        self::assertSame(12, $updates[0][0]);
        self::assertArrayHasKey('exec_time', $updates[0][1]);
        self::assertSame([12, [
            'result_data' => json_encode($result),
            'added_by_listener' => 1,
        ]], $updates[1]);
    }
}
