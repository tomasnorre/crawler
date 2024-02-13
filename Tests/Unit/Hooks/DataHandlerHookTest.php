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
class DataHandlerHookTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    use ProphecyTrait;

    protected function tearDown(): void
    {
        $this->resetSingletonInstances = true;
    }

    /**
     * Page with ID 1 is not in queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     *
     * @test
     */
    public function itShouldAddPageToQueue(): void
    {
        $mockedDataHandlerHook = $this->createPartialMock(
            DataHandlerHook::class,
            ['getQueueRepository', 'getQueueService', 'getPageRepository']
        );

        $queueService = $this->prophesize(QueueService::class);
        $queueService->addPageToQueue(1)->shouldBeCalled();

        $queueRepository = $this->prophesize(QueueRepository::class);
        $queueRepository->isPageInQueue(1)->willReturn(false);
        $queueRepository->isPageInQueue(2)->willReturn(true);

        $pageRepository = $this->prophesize(PageRepository::class);
        $pageRepository->getPage(1)->willReturn(['Faking that page exists as not empty array']);
        $pageRepository->getPage(2)->willReturn(['Faking that page exists as not empty array']);

        $mockedDataHandlerHook->method('getQueueRepository')->willReturn($queueRepository->reveal());
        $mockedDataHandlerHook->method('getQueueService')->willReturn($queueService->reveal());
        $mockedDataHandlerHook->method('getPageRepository')->willReturn($pageRepository->reveal());

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $dataHandler = new DataHandler();

        $mockedDataHandlerHook->addFlushedPagesToCrawlerQueue(
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
        $mockedDataHandlerHook = $this->createPartialMock(
            DataHandlerHook::class,
            ['getQueueRepository', 'getQueueService', 'getPageRepository']
        );

        $queueService = $this->prophesize(QueueService::class);
        $queueService->addPageToQueue(1)->shouldBeCalled();
        $queueService->addPageToQueue(3)->shouldBeCalled();

        $queueRepository = $this->prophesize(QueueRepository::class);
        $queueRepository->isPageInQueue(1)->willReturn(false);
        $queueRepository->isPageInQueue(2)->willReturn(true);
        $queueRepository->isPageInQueue(3)->willReturn(false);

        $pageRepository = $this->prophesize(PageRepository::class);
        $pageRepository->getPage(1)->willReturn(['Faking that page exists as not empty array']);
        $pageRepository->getPage(2)->willReturn(['Faking that page exists as not empty array']);
        $pageRepository->getPage(3)->willReturn(['Faking that page exists as not empty array']);

        $mockedDataHandlerHook->method('getQueueRepository')->willReturn($queueRepository->reveal());
        $mockedDataHandlerHook->method('getQueueService')->willReturn($queueService->reveal());
        $mockedDataHandlerHook->method('getPageRepository')->willReturn($pageRepository->reveal());

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $dataHandler = new DataHandler();

        $mockedDataHandlerHook->addFlushedPagesToCrawlerQueue(
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
        $mockedDataHandlerHook = $this->createPartialMock(
            DataHandlerHook::class,
            ['getQueueRepository', 'getQueueService', 'getPageRepository']
        );

        $queueService = $this->prophesize(QueueService::class);
        $queueService->addPageToQueue(1)->shouldBeCalled();

        $queueRepository = $this->prophesize(QueueRepository::class);
        $queueRepository->isPageInQueue(1)->willReturn(false);

        $pageRepository = $this->prophesize(PageRepository::class);
        $pageRepository->getPage(1)->willReturn(['Faking that page exists as not empty array']);
        // Empty array to act like pages doesn't exist
        $pageRepository->getPage(3000)->willReturn([]);

        $mockedDataHandlerHook->method('getQueueRepository')->willReturn($queueRepository->reveal());
        $mockedDataHandlerHook->method('getQueueService')->willReturn($queueService->reveal());
        $mockedDataHandlerHook->method('getPageRepository')->willReturn($pageRepository->reveal());

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $dataHandler = new DataHandler();

        $mockedDataHandlerHook->addFlushedPagesToCrawlerQueue(
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
        $mockedDataHandlerHook = $this->createPartialMock(
            DataHandlerHook::class,
            ['getQueueRepository', 'getQueueService', 'getPageRepository']
        );

        $queueService = $this->prophesize(QueueService::class);
        $queueService->addPageToQueue(1)->shouldBeCalled();

        $queueRepository = $this->prophesize(QueueRepository::class);
        $queueRepository->isPageInQueue(1)->willReturn(false);
        $queueRepository->isPageInQueue(2)->willReturn(true);

        $pageRepository = $this->prophesize(PageRepository::class);
        $pageRepository->getPage(1)->willReturn(['Faking that page exists as not empty array']);
        $pageRepository->getPage(2)->willReturn(['Faking that page exists as not empty array']);

        $mockedDataHandlerHook->method('getQueueRepository')->willReturn($queueRepository->reveal());
        $mockedDataHandlerHook->method('getQueueService')->willReturn($queueService->reveal());
        $mockedDataHandlerHook->method('getPageRepository')->willReturn($pageRepository->reveal());

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $dataHandler = new DataHandler();

        $mockedDataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'table' => 'pages',
                'pageIdArray' => ['0', '1', '2'],
            ],
            $dataHandler
        );
    }
}
