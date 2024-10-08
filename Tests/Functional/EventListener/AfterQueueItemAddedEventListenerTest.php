<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\EventListener;

use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\AfterQueueItemAddedEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AfterQueueItemAddedEventListenerTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    private EventDispatcher $eventDispatcher;
    private QueueRepository $queueRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        $this->queueRepository = GeneralUtility::makeInstance(QueueRepository::class);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.csv');
    }

    #[Test]
    public function listenerIsInvoked(): void
    {
        $event = new AfterQueueItemAddedEvent(1001, [
            'parameters' => 'test params',
        ]);
        $this->eventDispatcher->dispatch($event);

        $queueItem = $this->queueRepository->getQueueEntriesByQid(1001, true);
        self::assertEquals('test params', $queueItem['parameters']);
    }
}
