<?php

namespace AOE\Crawler\Tests\Unit\Service;

use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Service\QueueService;
use AOE\Crawler\Value\QueueRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(QueueService::class)]
class QueueServiceTest extends UnitTestCase
{
    private QueueService $subject;

    protected function setUp(): void
    {

        $this->subject = new QueueService();
        parent::setUp();
    }

    #[Test]
    public function getPageFromQueueReturnEmptyArrayIfQueueIsEmpty(): void
    {
        $queueRepository = $this->getMockBuilder(QueueRepository::class)->disableOriginalConstructor()->getMock();
        self:
        self::assertEquals([], $this->subject->getPageFromQueue($queueRepository));
    }

    #[Test]
    public function getPageFromQueueReturnAllEntriesFromQueue(): void
    {
        $queueRepository = $this->getMockBuilder(QueueRepository::class)->disableOriginalConstructor()->getMock();
        $queueRepository->expects($this->any())->method('getUnprocessedItems')->willReturn(
            $this->getMockedQueueRepositoryData()
        );

        $queueRows = $this->subject->getPageFromQueue($queueRepository);
        $firstQueueRow = $queueRows[0];

        self::assertCount(1, $queueRows);
        self::assertInstanceOf(QueueRow::class, $firstQueueRow);

        self::assertEquals('FirstConfiguration', $firstQueueRow->configurationKey);
        self::assertEquals('', $firstQueueRow->parameters);
    }

    private function getMockedQueueRepositoryData(): array
    {
        return [
            [
                'qid' => 1001,
                'process_id' => 1001,
                'page_id' => 0,
                'process_id_completed' => 'asdfgh',
                'parameters' => '',
                'result_data' => '',
                'exec_time' => 10,
                'scheduled' => 0,
                'configuration' => 'FirstConfiguration',
                'parameters_hash' => '',
                'configuration_hash' => '',
                'set_id' => 0,
            ],
        ];
    }

}
