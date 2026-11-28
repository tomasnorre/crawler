<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue\Handler;

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
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Service\PageService;
use AOE\Crawler\Value\CrawlResult;
use AOE\Crawler\Value\QueueEntry;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Crawls the URL of a queue record and stores the result on the record.
 *
 * @internal
 */
#[AsMessageHandler]
final readonly class CrawlUrlHandler
{
    public function __construct(
        private QueueRepository $queueRepository,
        private QueueExecutor $queueExecutor,
        private EventDispatcherInterface $eventDispatcher,
        private ClockInterface $clock,
        private PageService $pageService,
    ) {
    }

    /**
     * @return CrawlResult|null The crawl result, or null if the queue record does not exist (anymore),
     *                          is already crawled or its page is excluded from crawling.
     */
    public function __invoke(CrawlUrlMessage $message): ?CrawlResult
    {
        $queueId = $message->queueId;
        $queueEntry = $this->queueRepository->getQueueEntriesByQid($queueId, $message->force);
        if (!$queueEntry instanceof QueueEntry) {
            return null;
        }

        // The page can be excluded from crawling after it was added to the queue
        if ($this->skipExcludedPage($queueId, $queueEntry->pageId)) {
            return null;
        }

        /** @var BeforeQueueItemAddedEvent $beforeEvent */
        $beforeEvent = $this->eventDispatcher->dispatch(new BeforeQueueItemAddedEvent($queueId, $queueEntry->toRow()));
        $queueEntry = QueueEntry::fromRow($beforeEvent->getQueueRecord());

        $this->queueRepository->update($queueId, [
            'exec_time' => $this->clock->now()->getTimestamp(),
        ]);

        $result = $this->queueExecutor->executeQueueItem($queueEntry);

        /** @var AfterQueueItemAddedEvent $afterEvent */
        $afterEvent = $this->eventDispatcher->dispatch(new AfterQueueItemAddedEvent($queueId, [
            'result_data' => $result->toResultData(),
        ]));
        $this->queueRepository->update($queueId, $afterEvent->getFieldArray());

        return $result;
    }

    /**
     * Marks the queue record as processed, with the reason as error in the result, if its page is excluded
     */
    private function skipExcludedPage(int $queueId, int $pageId): bool
    {
        $exclusionReason = $this->pageService->getExclusionReason($pageId);
        if ($exclusionReason === false) {
            return false;
        }

        $this->queueRepository->update($queueId, [
            'exec_time' => $this->clock->now()->getTimestamp(),
            'result_data' => CrawlResult::error('Skipped: ' . $exclusionReason)->toResultData(),
        ]);
        return true;
    }
}
