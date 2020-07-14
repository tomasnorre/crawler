<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Hooks;

use AOE\Crawler\Api\CrawlerApi;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Hooks\DataHandlerHook;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DataHandlerHookTest extends UnitTestCase
{
    /**
     * Page with ID 1 is not queue, should be added
     * Page with ID 2 is already in queue. Should NOT be added.
     *
     * @test
     */
    public function itShouldAddPageToQueue(): void
    {
        $dataHandlerHook = new DataHandlerHook();

        $crawlerApi = $this->prophesize(CrawlerApi::class);
        $crawlerApi->addPageToQueue(1)->shouldBeCalled();
        $queueRepository = $this->prophesize(QueueRepository::class);
        $queueRepository->isPageInQueue(1)->willReturn(false);
        $queueRepository->isPageInQueue(2)->willReturn(true);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(QueueRepository::class)->willReturn($queueRepository->reveal());
        $objectManager->get(CrawlerApi::class)->willReturn($crawlerApi->reveal());

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache(Argument::any())->willReturn($this->prophesize(FrontendInterface::class)->reveal());

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());

        $beUser = new BackendUserAuthentication();
        $beUser->workspace = 1;

        $dataHandler = new DataHandler();
        $cmd = ['pages' => [123 => ['version' => ['action' => 'swap']]]];
        $dataHandler->start([], $cmd, $beUser);

        $dataHandlerHook->addFlushedPagesToCrawlerQueue(
            [
                'pageIdArray' => [1, 2]
            ],
            $dataHandler
        );
    }
}
