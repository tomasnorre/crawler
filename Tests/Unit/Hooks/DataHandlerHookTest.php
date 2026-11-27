<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Hooks;

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
use AOE\Crawler\Hooks\DataHandlerHook;
use AOE\Crawler\Service\QueueService;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Hooks\DataHandlerHook::class)]
class DataHandlerHookTest extends UnitTestCase
{
    private const EXISTING_PAGE = ['Faking that page exists as not empty array'];

    /**
     * @var list<int>
     */
    private array $pagesAddedToQueue = [];

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function itShouldAddPageToQueue(): void
    {
        $dataHandlerHook = $this->createDataHandlerHook(
            [
                1 => self::EXISTING_PAGE,
                2 => self::EXISTING_PAGE,
            ],
            [
                1 => false,
                2 => true,
            ]
        );

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'pages',
                'pageIdArray' => [0, 1, 2],
            ],
            $this->createStub(DataHandler::class)
        );

        self::assertSame([1], $this->pagesAddedToQueue);
    }

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     * Page with ID 3 is not in queue, should be added
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function itShouldAddPageToQueueWithMorePages(): void
    {
        $dataHandlerHook = $this->createDataHandlerHook(
            [
                1 => self::EXISTING_PAGE,
                2 => self::EXISTING_PAGE,
                3 => self::EXISTING_PAGE,
            ],
            [
                1 => false,
                2 => true,
                3 => false,
            ]
        );

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'tt_content',
                'pageIdArray' => [0, 1, 2, 3],
            ],
            $this->createStub(DataHandler::class)
        );

        self::assertSame([1, 3], $this->pagesAddedToQueue);
    }

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 3000 does not exist. Should NOT be added.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function nothingToBeAddedAsPageDoNotExists(): void
    {
        $dataHandlerHook = $this->createDataHandlerHook(
            [
                1 => self::EXISTING_PAGE,
                // Empty array to act like pages doesn't exist
                3000 => [],
            ],
            [
                1 => false,
            ]
        );

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'tt_content',
                'pageIdArray' => [0, 1, 3000],
            ],
            $this->createStub(DataHandler::class)
        );

        self::assertSame([1], $this->pagesAddedToQueue);
    }

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function ensureThatPageIdArrayIsConvertedToInteger(): void
    {
        $dataHandlerHook = $this->createDataHandlerHook(
            [
                1 => self::EXISTING_PAGE,
                2 => self::EXISTING_PAGE,
            ],
            [
                1 => false,
                2 => true,
            ]
        );

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'pages',
                'pageIdArray' => ['0', '1', '2'],
            ],
            $this->createStub(DataHandler::class)
        );

        self::assertSame([1], $this->pagesAddedToQueue);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ensureEarlyReturnIfPageIdsToBeFlushedFromCacheEmpty(): void
    {
        $queueService = $this->createMock(QueueService::class);
        $queueService->expects(self::never())->method('addPageToQueue');

        $queueRepository = $this->createMock(QueueRepository::class);
        $queueRepository->expects(self::never())->method('isPageInQueue');

        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects(self::never())->method('getPage');

        $dataHandlerHook = $this->createPartialMock(
            DataHandlerHook::class,
            ['getQueueRepository', 'getQueueService', 'getPageRepository']
        );
        $dataHandlerHook->method('getQueueRepository')->willReturn($queueRepository);
        $dataHandlerHook->method('getQueueService')->willReturn($queueService);
        $dataHandlerHook->method('getPageRepository')->willReturn($pageRepository);

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'pages',
                'pageIdArray' => [],
            ],
            $this->createStub(DataHandler::class)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function gettersReturnInstancesFromGeneralUtility(): void
    {
        $queueRepository = $this->createStub(QueueRepository::class);
        $queueService = $this->createStub(QueueService::class);
        $pageRepository = $this->createStub(PageRepository::class);
        GeneralUtility::addInstance(QueueRepository::class, $queueRepository);
        GeneralUtility::addInstance(QueueService::class, $queueService);
        GeneralUtility::addInstance(PageRepository::class, $pageRepository);

        $dataHandlerHook = new DataHandlerHook();

        self::assertSame($queueRepository, $dataHandlerHook->getQueueRepository());
        self::assertSame($queueService, $dataHandlerHook->getQueueService());
        self::assertSame($pageRepository, $dataHandlerHook->getPageRepository());
    }

    /**
     * Every page added to the queue is recorded in $this->pagesAddedToQueue.
     * Asking for a page or a queue state that isn't given here fails the test.
     *
     * @param array<int, array<mixed>> $pages page ID => page record
     * @param array<int, bool> $pagesInQueue page ID => already in queue
     */
    private function createDataHandlerHook(array $pages, array $pagesInQueue): DataHandlerHook&MockObject
    {
        $pageRepository = $this->createStub(PageRepository::class);
        $pageRepository->method('getPage')->willReturnCallback(
            static function (int $pageId) use ($pages): array {
                self::assertArrayHasKey($pageId, $pages, 'Unexpected call to getPage(' . $pageId . ')');
                return $pages[$pageId];
            }
        );

        $queueRepository = $this->createStub(QueueRepository::class);
        $queueRepository->method('isPageInQueue')->willReturnCallback(
            static function (int $pageId) use ($pagesInQueue): bool {
                self::assertArrayHasKey(
                    $pageId,
                    $pagesInQueue,
                    'Unexpected call to isPageInQueue(' . $pageId . ')'
                );
                return $pagesInQueue[$pageId];
            }
        );

        $queueService = $this->createStub(QueueService::class);
        $queueService->method('addPageToQueue')->willReturnCallback(function (int $pageId): void {
            $this->pagesAddedToQueue[] = $pageId;
        });

        $dataHandlerHook = $this->createPartialMock(
            DataHandlerHook::class,
            ['getQueueRepository', 'getQueueService', 'getPageRepository']
        );
        $dataHandlerHook->method('getQueueRepository')->willReturn($queueRepository);
        $dataHandlerHook->method('getQueueService')->willReturn($queueService);
        $dataHandlerHook->method('getPageRepository')->willReturn($pageRepository);

        return $dataHandlerHook;
    }
}
