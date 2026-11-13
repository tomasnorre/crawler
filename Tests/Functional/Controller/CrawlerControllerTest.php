<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Controller;

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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @package AOE\Crawler\Tests\Functional\Controller
 */
class CrawlerControllerTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;

    protected array $coreExtensionsToLoad = ['seo'];

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected AccessibleObjectInterface|MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupBackendRequest();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_configuration.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');

        $this->subject = $this->getAccessibleMock(CrawlerController::class, null);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getConfigurationsForBranch(): void
    {
        $GLOBALS['BE_USER'] = $this->createStub(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->method('isAdmin')->willReturn(true);

        $configurationsForBranch = $this->subject->getConfigurationsForBranch(5, 99);

        self::assertNotEmpty($configurationsForBranch);
        self::assertCount(4, $configurationsForBranch);

        // sort is done as MySQL and SQLite doesn't sort the same way even though sorting is by "name ASC"
        sort($configurationsForBranch);
        $expected = [
            'default',
            'Not hidden or deleted',
            'Not hidden or deleted - uid 5',
            'Not hidden or deleted - uid 6',
        ];
        sort($expected);

        self::assertEquals($expected, $configurationsForBranch);
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

        $mockedCrawlerController = $this->getAccessibleMock(CrawlerController::class, null);

        $mockedCrawlerController->_set('registerQueueEntriesInternallyOnly', $registerQueueEntriesInternallyOnly);
        $mockedCrawlerController->_set('queueRepository', $mockedQueueRepository);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($expected ? self::once() : self::never())
            ->method('dispatch')
            ->with(self::isInstanceOf(CrawlUrlMessage::class), self::isArray())
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));
        $mockedCrawlerController->injectCrawlUrlMessageDispatcher(new CrawlUrlMessageDispatcher($messageBus));

        self::assertEquals(
            $expected,
            $mockedCrawlerController->addUrl(
                $id,
                $url,
                $subCfg,
                $tstamp,
                $configurationHash,
                $skipInnerDuplicationCheck
            )
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function addUrlDelaysMessageForEntriesScheduledInTheFuture(): void
    {
        $mockedQueueRepository = $this->getAccessibleMock(QueueRepository::class, ['getDuplicateQueueItemsIfExists']);
        $mockedQueueRepository->method('getDuplicateQueueItemsIfExists')->willReturn([]);

        $crawlerController = $this->getAccessibleMock(CrawlerController::class, ['getCurrentTime']);
        $crawlerController->method('getCurrentTime')->willReturn(1_000_000);
        $crawlerController->_set('queueRepository', $mockedQueueRepository);

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
        $crawlerController->injectCrawlUrlMessageDispatcher(new CrawlUrlMessageDispatcher($messageBus));

        self::assertTrue($crawlerController->addUrl(1, 'https://example.com/', [
            'key' => 'some-key',
        ], 1_000_060));
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
