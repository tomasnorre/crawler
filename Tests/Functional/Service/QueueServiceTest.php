<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Service;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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
use AOE\Crawler\Service\QueueService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class QueueServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var QueueService
     */
    protected $subject;

    protected \AOE\Crawler\Domain\Repository\QueueRepository $queueRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupExtensionSettings();

        $crawlerController = $this->getAccessibleMock(CrawlerController::class, ['getCurrentTime'], [], '');
        $crawlerController->method('getCurrentTime')->willReturn(100000);

        $this->subject = $this->createPartialMock(QueueService::class, []);
        $this->subject->injectCrawlerController($crawlerController);
        $this->queueRepository = GeneralUtility::makeInstance(QueueRepository::class);
    }

    /**
     * This test is used to check that the api will not create duplicate entries for
     * two pages which should both be crawled in the past, because it is only needed one times.
     * The testcase uses a TSConfig crawler configuration.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function canNotCreateDuplicateQueueEntriesForTwoPagesInThePast(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../data/canNotAddDuplicatePagesToQueue.csv');

        $this->subject->addPageToQueue(5, 9998);
        $this->subject->addPageToQueue(5, 3422);

        self::assertCount(1, $this->queueRepository->getUnprocessedItems());
    }

    /**
     * This test should check that the api does not create two queue entries for
     * two pages which should be crawled at the same time in the future.
     * The testcase uses a TSConfig crawler configuration.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function canNotCreateDuplicateForTwoPagesInTheFutureWithTheSameTimestamp(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../data/canNotAddDuplicatePagesToQueue.csv');

        $this->subject->addPageToQueue(5, 100001);
        $this->subject->addPageToQueue(5, 100001);

        self::assertCount(1, $this->queueRepository->getUnprocessedItems());
    }

    /**
     * This test is used to check that the api can be used to schedule one page two times
     * for a different timestamp in the future.
     * The testcase uses a TSConfig crawler configuration.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function canCreateTwoQueueEntriesForDifferentTimestampsInTheFuture(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../data/canNotAddDuplicatePagesToQueue.csv');

        $this->subject->addPageToQueue(5, 100011);
        $this->subject->addPageToQueue(5, 200014);

        self::assertCount(2, $this->queueRepository->getUnprocessedItems());
    }

    private function setupExtensionSettings(): void
    {
        $configuration = [
            'sleepTime' => '1000',
            'sleepAfterFinish' => '10',
            'countInARun' => '100',
            'purgeQueueDays' => '14',
            'processLimit' => '1',
            'processMaxRunTime' => '300',
            'maxCompileUrls' => '10000',
            'processDebug' => '0',
            'processVerbose' => '0',
            'crawlHiddenPages' => '0',
            'phpPath' => '/usr/bin/php',
            'enableTimeslot' => '1',
            'makeDirectRequests' => '0',
            'frontendBasePath' => '/',
            'cleanUpOldQueueEntries' => '1',
            'cleanUpProcessedAge' => '2',
            'cleanUpScheduledAge' => '7',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
    }
}
