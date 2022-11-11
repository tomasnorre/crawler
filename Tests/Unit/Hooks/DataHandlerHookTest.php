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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @covers \AOE\Crawler\Hooks\DataHandlerHook
 */
class DataHandlerHookTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     *
     * @test
     */
    public function itShouldAddPageToQueue(): void
    {
        $pageRepositoryMock = $this->createMock(PageRepository::class);
        $pageRepositoryMock->expects($this->exactly(2))->method('getPage')
            ->willReturn(['Faking that page exists as not empty array']);

        $queueRepositoryMock = $this->createMock(QueueRepository::class);
        $queueRepositoryMock->expects($this->exactly(2))->method('isPageInQueue')
            ->withConsecutive([1],[2])
            ->willReturnOnConsecutiveCalls(false, true);

        $queueServiceMock = $this->createMock(QueueService::class);
        $queueServiceMock->expects($this->once())->method('addPageToQueue')->with(1);

        $dataHandlerHook = new DataHandlerHook($pageRepositoryMock, $queueRepositoryMock, $queueServiceMock);

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $dataHandler = new DataHandler();

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'pages',
                'pageIdArray' => [0, 1, 2],
            ],
            $dataHandler
        );
    }

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     * Page with ID 3 is not in queue, should be added
     *
     * @test
     */
    public function itShouldAddPageToQueueWithMorePages(): void
    {
        $pageRepositoryMock = $this->createMock(PageRepository::class);
        $pageRepositoryMock->expects($this->exactly(3))->method('getPage')
            ->willReturn(['Faking that page exists as not empty array']);

        $queueRepositoryMock = $this->createMock(QueueRepository::class);
        $queueRepositoryMock->method('isPageInQueue')
            ->withConsecutive([1],[2],[3])
            ->willReturnOnConsecutiveCalls(false, true, false);

        $queueServiceMock = $this->createMock(QueueService::class);
        $queueServiceMock->expects($this->exactly(2))->method('addPageToQueue')->withConsecutive([1],[3]);

        $dataHandlerHook = new DataHandlerHook($pageRepositoryMock, $queueRepositoryMock, $queueServiceMock);

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $dataHandler = new DataHandler();

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'tt_content',
                'pageIdArray' => [0, 1, 2, 3],
            ],
            $dataHandler
        );
    }

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     * Page with ID 3 is not in queue, should be added
     *
     * @test
     */
    public function nothingToBeAddedAsPageDoNotExists(): void
    {
        $pageRepositoryMock = $this->createMock(PageRepository::class);
        $pageRepositoryMock->expects($this->exactly(2))->method('getPage')
            ->withConsecutive([1],[3000])
            ->willReturnOnConsecutiveCalls(
                ['Faking that page exists as not empty array'],
                [] // Empty array to act like pages doesn't exist
            );

        $queueRepositoryMock = $this->createMock(QueueRepository::class);
        $queueRepositoryMock->expects($this->once())->method('isPageInQueue')->with(1)->willReturn(false);

        $queueServiceMock = $this->createMock(QueueService::class);
        $queueServiceMock->expects($this->once())->method('addPageToQueue')->with(1);

        $dataHandlerHook = new DataHandlerHook($pageRepositoryMock, $queueRepositoryMock, $queueServiceMock);

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $dataHandler = new DataHandler();

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'tt_content',
                'pageIdArray' => [0, 1, 3000],
            ],
            $dataHandler
        );
    }

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     *
     * @test
     */
    public function ensureThatPageIdArrayIsConvertedToInteger(): void
    {
        $pageRepositoryMock = $this->createMock(PageRepository::class);
        $pageRepositoryMock->expects($this->exactly(2))->method('getPage')
            ->willReturn(['Faking that page exists as not empty array']);

        $queueRepositoryMock = $this->createMock(QueueRepository::class);
        $queueRepositoryMock->expects($this->exactly(2))->method('isPageInQueue')
            ->withConsecutive([1],[2])
            ->willReturnOnConsecutiveCalls(false, true);

        $queueServiceMock = $this->createMock(QueueService::class);
        $queueServiceMock->expects($this->once())->method('addPageToQueue')->with(1);

        $dataHandlerHook = new DataHandlerHook($pageRepositoryMock, $queueRepositoryMock, $queueServiceMock);

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $dataHandler = new DataHandler();

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'pages',
                'pageIdArray' => ['0', '1', '2'],
            ],
            $dataHandler
        );
    }
}
