<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Hooks;

use AOE\Crawler\Api\CrawlerApi;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Hooks\DataHandlerHook;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DataHandlerHookTest extends UnitTestCase
{
    /**
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

        GeneralUtility::addInstance(ObjectManager::class, $objectManager->reveal());

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
