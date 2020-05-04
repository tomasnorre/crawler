<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Api;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Api\CrawlerApi;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Tests\Functional\SiteBasedTestTrait;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class CrawlerApiTest
 *
 * @package AOE\Crawler\Tests\Functional\Api
 */
class CrawlerApiTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'FR-CA' => ['id' => 2, 'title' => 'Franco-Canadian', 'locale' => 'fr_CA.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-CA', 'direction' => ''],
    ];

    /**
     * @var CrawlerApi
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var array stores the old rootline
     */
    protected $oldRootline;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        //restore old rootline
        $this->oldRootline = $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'];
        //clear rootline
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = '';

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

        $this->subject = $objectManager->get(CrawlerApi::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);

        $this->importDataSet(__DIR__ . '/../data/pages.xml');
        $this->importDataSet(__DIR__ . '/../data/sys_template.xml');

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        //restore rootline
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = $this->oldRootline;
    }

    /**
     * @test
     */
    public function findCrawlerReturnsCrawlerObject(): void
    {
        self::assertInstanceOf(
            CrawlerController::class,
            $this->callInaccessibleMethod($this->subject, 'findCrawler')
        );
    }

    /**
     * @test
     */
    public function overwriteSetId(): void
    {
        $newId = 12345;
        $this->subject->overwriteSetId($newId);

        self::assertSame(
            $newId,
            $this->subject->getSetId()
        );
    }

    /**
     * @test
     */
    public function setAllowedConfigurations(): void
    {
        $newConfiguration = [
            'simple_array_values',
            'that_has_no_use',
            'in_this_test',
            'besides_the_comparison',
            'with_before_and_after',
        ];

        $this->subject->setAllowedConfigurations($newConfiguration);

        self::assertSame(
            $newConfiguration,
            $this->subject->getAllowedConfigurations()
        );
    }

    /**
     * @test
     */
    public function getLatestCrawlTimestampForPage(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');

        self::assertSame(
            4321,
            $this->subject->getLatestCrawlTimestampForPage(17)
        );
    }

    /**
     * @test
     */
    public function getCrawlHistoryForPage(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');

        self::assertEquals(
            [
                [
                    'scheduled' => 4321,
                    'exec_time' => 20,
                    'set_id' => 0,
                ],
            ],
            $this->subject->getCrawlHistoryForPage(17, 1)
        );
    }

    /**
     * @test
     */
    public function getQueueStatistics(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_crawler_queue.xml');

        self::assertEquals(
            [
                'assignedButUnprocessed' => 3,
                'unprocessed' => 7,
            ],
            $this->subject->getQueueStatistics()
        );
    }

    /**
     * This test is used to check that the api will not create duplicate entries for
     * two pages which should both be crawled in the past, because it is only needed one times.
     * The testcase uses a TSConfig crawler configuration.
     *
     * @test
     */
    public function canNotCreateDuplicateQueueEntriesForTwoPagesInThePast(): void
    {
        $this->importDataSet(__DIR__ . '/../data/canNotAddDuplicatePagesToQueue.xml');

        $crawlerApi = $this->getMockedCrawlerAPI(100000);

        $crawlerApi->addPageToQueueTimed(5, 9998);
        $crawlerApi->addPageToQueueTimed(5, 3422);

        self::assertSame(
            $this->queueRepository->countUnprocessedItems(),
            1
        );
    }

    /**
     * This test should check that the api does not create two queue entries for
     * two pages which should be crawled at the same time in the future.
     * The testcase uses a TSConfig crawler configuration.
     *
     * @test
     */
    public function canNotCreateDuplicateForTwoPagesInTheFutureWithTheSameTimestamp(): void
    {
        $this->importDataSet(__DIR__ . '/../data/canNotAddDuplicatePagesToQueue.xml');

        $crawlerApi = $this->getMockedCrawlerAPI(100000);

        $crawlerApi->addPageToQueueTimed(5, 100001);
        $crawlerApi->addPageToQueueTimed(5, 100001);

        self::assertSame(
            $this->queueRepository->countUnprocessedItems(),
            1
        );
    }

    /**
     * This test is used to check that the api can be used to schedule one  page two times
     * for a diffrent timestamp in the future.
     * The testcase uses a TSConfig crawler configuration.
     *
     * @test
     */
    public function canCreateTwoQueueEntriesForDiffrentTimestampsInTheFuture(): void
    {
        $this->importDataSet(__DIR__ . '/../data/canNotAddDuplicatePagesToQueue.xml');

        $crawlerApi = $this->getMockedCrawlerAPI(100000);

        $crawlerApi->addPageToQueueTimed(5, 100011);
        $crawlerApi->addPageToQueueTimed(5, 100014);

        self::assertEquals($this->queueRepository->countUnprocessedItems(), 2);
    }

    /**
     * This testcase is used to check that pages can be queued in an environment.
     * Where the crawler is configured using configuration records instead of pagets config.
     *
     * @test
     */
    public function canCreateQueueEntriesUsingConfigurationRecord(): void
    {
        $expectedParameterData = [
            'url' => 'http://www.testcase.de/index.php?id=7&L=0&S=CRAWL&cHash=966b79fa43675d725b45415c54ea0cb7',
            'procInstructions' => [
                0 => 'tx_staticpub_publish',
            ],
            'procInstrParams' => [
                'tx_staticpub_publish.' => [
                    'includeResources' => 'relPath',
                ],
            ],
        ];
        $expectedParameter = json_encode($expectedParameterData);

        $this->importDataSet(__DIR__ . '/../data/canCreateQueueEntriesUsingConfigurationRecord.xml');
        $crawlerApi = $this->getMockedCrawlerAPI(100000);
        $crawlerApi->addPageToQueueTimed(7, 100011);
        $crawlerApi->addPageToQueueTimed(7, 100059);

        $queueItems = $this->queueRepository->getUnprocessedItems();
        //$queueItems = $crawlerApi->getUnprocessedItems();

        self::assertEquals($queueItems[0]['page_id'], 7);
        self::assertEquals($queueItems[0]['scheduled'], 100011);
        self::assertEquals(
            $expectedParameter,
            $queueItems[0]['parameters'],
            'Wrong queue parameters created by crawler lib for configuration record'
        );

        self::assertEquals($queueItems[1]['page_id'], 7);
        self::assertEquals($queueItems[1]['scheduled'], 100059);
        self::assertEquals(
            $expectedParameter,
            $queueItems[1]['parameters'],
            'Wrong queue parameters created by crawler lib for configuration record'
        );

        self::assertEquals(
            $this->queueRepository->countUnprocessedItems(),
            2,
            'Could not add pages to queue configured by record'
        );
    }

    /**
     * Creates a mocked crawler api with a faked current time state
     *
     * @param int $currentTime
     *
     * @return CrawlerApi
     */
    protected function getMockedCrawlerAPI($currentTime)
    {
        //created mocked crawler controller which returns a faked timestamp
        $crawlerController = $this->getAccessibleMock(CrawlerController::class, ['getCurrentTime', 'drawURLs_PIfilter'], [], '');
        $crawlerController->expects($this->any())->method('getCurrentTime')->will($this->returnValue($currentTime));
        $crawlerController->expects($this->any())->method('drawURLs_PIfilter')->will($this->returnValue(true));

        /* @var CrawlerApi $crawlerApi */
        //create mocked api
        $crawlerApi = $this->createPartialMock(CrawlerApi::class, ['findCrawler']);
        $crawlerApi->expects($this->any())->method('findCrawler')->will($this->returnValue($crawlerController));

        return $crawlerApi;
    }
}
