<?php

declare(strict_types=1);

namespace AOE\Crawler\EventListener;

use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\AfterQueueItemAddedEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AfterQueueItemAddedEventListener
{
    public function __invoke(AfterQueueItemAddedEvent $event): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(QueueRepository::TABLE_NAME)
            ->update(QueueRepository::TABLE_NAME, $event->getFieldArray(), [
                'qid' => (int) $event->getQueueId(),
            ]);
    }
}
