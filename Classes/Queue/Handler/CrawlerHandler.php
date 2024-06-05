<?php

declare(strict_types=1);

namespace AOE\Crawler\Queue\Handler;

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\AfterQueueItemAddedEvent;
use AOE\Crawler\Event\BeforeQueueItemAddedEvent;
use AOE\Crawler\Queue\Message\CrawlerMessage;
use AOE\Crawler\QueueExecutor;
use PDO;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CrawlerHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly JsonCompatibilityConverter $jsonCompatibilityConverter,
        private readonly QueueExecutor $queueExecutor,
    )
    {    }

    public function __invoke(CrawlerMessage $crawlerMessage)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            QueueRepository::TABLE_NAME
        );
        $ret = 0;
        $this->logger?->debug('crawler-readurl start ' . microtime(true));

        $queryBuilder
            ->select('*')
            ->from(QueueRepository::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('qid', $queryBuilder->createNamedParameter($crawlerMessage->id, PDO::PARAM_INT))
            );
        $queueRec = $queryBuilder->executeQuery()->fetchAssociative();


        if (!is_array($queueRec)) {
            return null;
        }

        /** @var BeforeQueueItemAddedEvent $event */
        $event = $this->eventDispatcher->dispatch(new BeforeQueueItemAddedEvent($crawlerMessage->id, $queueRec));
        $queueRec = $event->getQueueRecord();

        // Set exec_time to lock record:
        $field_array = [
            'exec_time' => time(),
        ];

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(QueueRepository::TABLE_NAME)
            ->update(QueueRepository::TABLE_NAME, $field_array, [
                'qid' => (int) $crawlerMessage->id
            ]);

        $crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
        $result = $this->queueExecutor->executeQueueItem($queueRec, $crawlerController);

        // Set result in log which also denotes the end of the processing of this entry.
        $field_array = [
            'result_data' => json_encode($result),
        ];

        /** @var AfterQueueItemAddedEvent $event */
        $event = $this->eventDispatcher->dispatch(new AfterQueueItemAddedEvent($crawlerMessage->id, $field_array));
        $field_array = $event->getFieldArray();

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(QueueRepository::TABLE_NAME)
            ->update(QueueRepository::TABLE_NAME, $field_array, [
                'qid' => $crawlerMessage->id,
            ]);

        $this->logger?->debug('crawler-readurl stop ' . microtime(true));
        error_log($crawlerMessage->content . chr(10) . '!! id:' . $crawlerMessage->id . chr(10), 3, '/tmp/tomas.log');
        return $ret;

    }
}
