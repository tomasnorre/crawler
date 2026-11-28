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
use AOE\Crawler\Service\PageService;
use AOE\Crawler\Tests\Unit\FrozenClock;
use AOE\Crawler\Value\CrawlResult;
use AOE\Crawler\Value\QueueEntry;
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
    private PageService&MockObject $pageService;
    private CrawlUrlHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueRepository = $this->createMock(QueueRepository::class);
        $this->queueExecutor = $this->createMock(QueueExecutor::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->pageService = $this->createMock(PageService::class);
        $this->subject = new CrawlUrlHandler(
            $this->queueRepository,
            $this->queueExecutor,
            $this->eventDispatcher,
            new FrozenClock(1_000_000),
            $this->pageService,
        );
    }

    #[Test]
    public function doesNothingIfQueueRecordIsNotFoundOrAlreadyCrawled(): void
    {
        $this->queueRepository->method('getQueueEntriesByQid')->with(12, false)->willReturn(null);
        $this->queueExecutor->expects(self::never())->method('executeQueueItem');
        $this->queueRepository->expects(self::never())->method('update');
        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->pageService->expects(self::never())->method('getExclusionReason');

        self::assertNull(($this->subject)(new CrawlUrlMessage(12)));
    }

    #[Test]
    public function doesNotCrawlQueueRecordOfPageExcludedFromCrawling(): void
    {
        $this->queueRepository->method('getQueueEntriesByQid')->with(12, false)->willReturn(new QueueEntry(
            qid: 12,
            pageId: 7,
            parameters: '{"url":"https://example.com/"}',
        ));
        $this->pageService->expects(self::once())->method('getExclusionReason')->with(7)
            ->willReturn('Because page is excluded from crawling');
        $this->queueExecutor->expects(self::never())->method('executeQueueItem');
        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->queueRepository->expects(self::once())->method('update')->with(12, [
            'exec_time' => 1_000_000,
            'result_data' => '{"content":"{\\"errorlog\\":[\\"Skipped: Because page is excluded from crawling\\"]}"}',
        ]);

        self::assertNull(($this->subject)(new CrawlUrlMessage(12)));
    }

    #[Test]
    public function crawlsQueueRecordOfPageNotExcludedFromCrawling(): void
    {
        $queueEntry = new QueueEntry(qid: 12, pageId: 7, parameters: '{"url":"https://example.com/"}');
        $this->queueRepository->method('getQueueEntriesByQid')->with(12, false)->willReturn($queueEntry);
        $this->pageService->expects(self::once())->method('getExclusionReason')->with(7)->willReturn(false);
        $this->eventDispatcher->method('dispatch')->willReturnArgument(0);
        $result = CrawlResult::fromContent([]);
        $this->queueExecutor->expects(self::once())->method('executeQueueItem')->with($queueEntry)->willReturn(
            $result
        );

        self::assertSame($result, ($this->subject)(new CrawlUrlMessage(12)));
    }

    #[Test]
    public function crawlsQueueRecordAndStoresResult(): void
    {
        $queueEntry = new QueueEntry(qid: 12, parameters: '{"url":"https://example.com/"}', setId: 34);
        $modifiedQueueEntry = new QueueEntry(qid: 12, parameters: '{"url":"https://example.com/other"}', setId: 34);
        $result = CrawlResult::fromContent([
            'vars' => [
                'status' => '200 OK',
            ],
        ]);

        $this->queueRepository->method('getQueueEntriesByQid')->with(12, true)->willReturn($queueEntry);
        // The record has no page_id, fx. a callback
        $this->pageService->expects(self::once())->method('getExclusionReason')->with(0)->willReturn(false);
        $this->eventDispatcher->expects(self::exactly(2))->method('dispatch')->willReturnCallback(
            static function (object $event) use ($queueEntry, $modifiedQueueEntry): object {
                if ($event instanceof BeforeQueueItemAddedEvent) {
                    self::assertSame(12, $event->getQueueId());
                    self::assertSame($queueEntry->toRow(), $event->getQueueRecord());
                    $event->setQueueRecord($modifiedQueueEntry->toRow());
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
            ->with($modifiedQueueEntry)
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
        self::assertSame([
            'exec_time' => 1_000_000,
        ], $updates[0][1]);
        self::assertSame([12, [
            'result_data' => '{"content":"{\\"vars\\":{\\"status\\":\\"200 OK\\"}}"}',
            'added_by_listener' => 1,
        ]], $updates[1]);
    }
}
