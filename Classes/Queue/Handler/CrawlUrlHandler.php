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
    ) {
    }

    /**
     * @return array|string|false|null The crawl result, see QueueExecutor::executeQueueItem(),
     *                                 or null if the queue record does not exist (anymore) or is already crawled.
     */
    public function __invoke(CrawlUrlMessage $message): array|string|false|null
    {
        $queueId = $message->queueId;
        $queueRecord = $this->queueRepository->getQueueEntriesByQid($queueId, $message->force);
        if (!is_array($queueRecord)) {
            return null;
        }

        /** @var BeforeQueueItemAddedEvent $beforeEvent */
        $beforeEvent = $this->eventDispatcher->dispatch(new BeforeQueueItemAddedEvent($queueId, $queueRecord));
        $queueRecord = $beforeEvent->getQueueRecord();

        $this->queueRepository->update($queueId, [
            'exec_time' => time(),
        ]);

        $result = $this->queueExecutor->executeQueueItem($queueRecord);

        /** @var AfterQueueItemAddedEvent $afterEvent */
        $afterEvent = $this->eventDispatcher->dispatch(new AfterQueueItemAddedEvent($queueId, [
            'result_data' => json_encode($result),
        ]));
        $this->queueRepository->update($queueId, $afterEvent->getFieldArray());

        return $result;
    }
}
