<?php
namespace AOE\Crawler\Tests\Functional\Api;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class CrawlerApiTest
 *
 * @package AOE\Crawler\Tests\Functional\Api
 */
class CrawlerApiTest extends FunctionalTestCase
{

    /**
     * @var CrawlerApi
     */
    protected $subject;

    /**
     * @var array
     */
    //protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     *
     * @var array stores the old rootline
     */
    protected $oldRootline;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * Creates the test environment.
     *
     */
    public function setUp()
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
            'logFileName' => '',
            'follow30x' => '0',
            'makeDirectRequests' => '0',
            'frontendBasePath' => '/',
            'cleanUpOldQueueEntries' => '1',
            'cleanUpProcessedAge' => '2',
            'cleanUpScheduledAge' => '7',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;

        $this->subject = $objectManager->get(CrawlerApi::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);

        $this->importDataSet(dirname(__FILE__) . '/../data/pages.xml');
        $this->importDataSet(dirname(__FILE__) . '/../data/sys_template.xml');
    }

    /**
     * Resets the test enviroment after the test.
     */
    public function tearDown()
    {
        parent::tearDown();
        //restore rootline
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = $this->oldRootline;
    }

    /**
     * @test
     */
    public function findCrawlerReturnsCrawlerObject()
    {
        $this->assertInstanceOf(
            CrawlerController::class,
            $this->callInaccessibleMethod($this->subject, 'findCrawler')
        );
    }

    /**
     * @test
     */
    public function overwriteSetId()
    {
        $newId = 12345;
        $this->subject->overwriteSetId($newId);

        $this->assertSame(
            $newId,
            $this->subject->getSetId()
        );
    }

    /**
     * @test
     */
    public function setAllowedConfigurations()
    {
        $newConfiguration = [
            'simple_array_values',
            'that_has_no_use',
            'in_this_test',
            'besides_the_comparison',
            'with_before_and_after',
        ];

        $this->subject->setAllowedConfigurations($newConfiguration);

        $this->assertSame(
            $newConfiguration,
            $this->subject->getAllowedConfigurations()
        );
    }

    /**
     * @test
     */
    public function countEntriesInQueueForPageByScheduleTime()
    {
        $this->importDataSet(dirname(__FILE__) . '/../Fixtures/tx_crawler_queue.xml');

        $this->assertSame(
            1,
            $this->callInaccessibleMethod($this->subject, 'countEntriesInQueueForPageByScheduleTime', 15, 0)
        );

        $this->assertSame(
            1,
            $this->callInaccessibleMethod($this->subject, 'countEntriesInQueueForPageByScheduleTime', 17, 4321)
        );
    }

    /**
     * @test
     */
    public function getLatestCrawlTimestampForPage()
    {
        $this->importDataSet(dirname(__FILE__) . '/../Fixtures/tx_crawler_queue.xml');

        $this->assertSame(
            4321,
            $this->subject->getLatestCrawlTimestampForPage(17)
        );
    }

    /**
     * @test
     */
    public function getCrawlHistoryForPage()
    {
        $this->importDataSet(dirname(__FILE__) . '/../Fixtures/tx_crawler_queue.xml');

        $this->assertEquals(
            [
                [
                    'scheduled' => 4321,
                    'exec_time' => 20,
                    'set_id' => 0
                ]
            ],
            $this->subject->getCrawlHistoryForPage(17, 1)
        );
    }

    /**
     * @test
     */
    public function getQueueStatistics()
    {
        $this->importDataSet(dirname(__FILE__) . '/../Fixtures/tx_crawler_queue.xml');

        $this->assertSame(
            [
                'assignedButUnprocessed' => 3,
                'unprocessed' => 5
            ],
            $this->subject->getQueueStatistics()
        );
    }

    /**
     * @test
     */
    public function getQueueRepository()
    {
        $this->assertInstanceOf(
            QueueRepository::class,
            $this->callInaccessibleMethod($this->subject, 'getQueueRepository')
        );
    }

    /**
     * This test is used to check that the api will not create duplicate entries for
     * two pages which should both be crawled in the past, because it is only needed one times.
     * The testcase uses a TSConfig crawler configuration.
     *
     * @test
     *
     * @return void
     */
    public function canNotCreateDuplicateQueueEntriesForTwoPagesInThePast()
    {
        $this->markTestSkipped('Is skipped, as we need to check how we are dealing with the find duplicates entries in queue');
        $this->importDataSet(dirname(__FILE__) . '/../data/canNotAddDuplicatePagesToQueue.xml');

        $crawlerApi = $this->getMockedCrawlerAPI(100000);

        $crawlerApi->addPageToQueueTimed(5, 9998);
        $crawlerApi->addPageToQueueTimed(5, 3422);

        $this->assertSame(
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
     *
     * @return void
     */
    public function canNotCreateDuplicateForTwoPagesInTheFutureWithTheSameTimestamp()
    {
        $this->markTestSkipped('Is skipped, as we need to check how we are dealing with the find duplicates entries in queue');
        $this->importDataSet(dirname(__FILE__) . '/../data/canNotAddDuplicatePagesToQueue.xml');

        $crawlerApi = $this->getMockedCrawlerAPI(100000);

        $crawlerApi->addPageToQueueTimed(5, 100001);
        $crawlerApi->addPageToQueueTimed(5, 100001);

        $this->assertSame(
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
     *
     * @return void
     */
    public function canCreateTwoQueueEntriesForDiffrentTimestampsInTheFuture()
    {
        $this->importDataSet(dirname(__FILE__) . '/../data/canNotAddDuplicatePagesToQueue.xml');

        $crawlerApi = $this->getMockedCrawlerAPI(100000);

        $crawlerApi->addPageToQueueTimed(5, 100011);
        $crawlerApi->addPageToQueueTimed(5, 100014);

        $this->assertEquals($this->queueRepository->countUnprocessedItems(), 2);
    }

    /**
     * This testcase is used to check that pages can be queued in an environment.
     * Where the crawler is configured using configuration records instead of pagets config.
     *
     * @test
     *
     * @return void
     */
    public function canCreateQueueEntriesUsingConfigurationRecord()
    {
        $expectedParameter = 'a:4:{s:3:"url";s:49:"http://www.testcase.de/index.php?id=7&L=0&S=CRAWL";s:16:"procInstructions";a:1:{i:0;s:20:"tx_staticpub_publish";}s:15:"procInstrParams";a:1:{s:21:"tx_staticpub_publish.";a:1:{s:16:"includeResources";s:7:"relPath";}}s:15:"rootTemplatePid";i:1;}';

        $this->importDataSet(dirname(__FILE__) . '/../data/canCreateQueueEntriesUsingConfigurationRecord.xml');
        $crawlerApi = $this->getMockedCrawlerAPI(100000);
        $crawlerApi->addPageToQueueTimed(7, 100011);
        $crawlerApi->addPageToQueueTimed(7, 100059);

        $queueItems = $this->queueRepository->getUnprocessedItems();
        //$queueItems = $crawlerApi->getUnprocessedItems();

        $this->assertEquals($queueItems[0]['page_id'], 7);
        $this->assertEquals($queueItems[0]['scheduled'], 100011);
        $this->assertEquals(
            $expectedParameter,
            $queueItems[0]['parameters'],
            'Wrong queue parameters created by crawler lib for configuration record'
        );

        $this->assertEquals($queueItems[1]['page_id'], 7);
        $this->assertEquals($queueItems[1]['scheduled'], 100059);
        $this->assertEquals(
            $expectedParameter,
            $queueItems[1]['parameters'],
            'Wrong queue parameters created by crawler lib for configuration record'
        );

        $this->assertEquals(
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
        $crawlerController->expects($this->any())->method("getCurrentTime")->will($this->returnValue($currentTime));
        $crawlerController->expects($this->any())->method("drawURLs_PIfilter")->will($this->returnValue(true));

        /* @var CrawlerApi $crawlerApi */
        //create mocked api
        $crawlerApi = $this->createPartialMock(CrawlerApi::class, ['findCrawler']);
        $crawlerApi->expects($this->any())->method("findCrawler")->will($this->returnValue($crawlerController));

        return $crawlerApi;
    }

    /**
     * @test
     */
    public function canReadHttpResponseFromStream()
    {
        require_once __DIR__ . '/../../Unit/Domain/Model/data/class.tx_crawler_lib_proxy.php';

        $dummyContent = 'Lorem ipsum';
        $dummyResponseHeader = [
            'HTTP/1.1 301 Moved Permanently',
            'Server: nginx',
            'Date: Fri, 25 Apr 2014 08:26:15 GMT',
            'Content-Type: text/html',
            'Content-Length: 11',
            'Connection: close',
        ];
        $dummyServerResponse = array_merge($dummyResponseHeader, ['', $dummyContent]);

        $fp = fopen('php://memory', 'rw');
        fwrite($fp, implode("\n", $dummyServerResponse));
        rewind($fp);

        $crawlerLibrary = new \tx_crawler_lib_proxy();
        $response = $crawlerLibrary->getHttpResponseFromStream($fp);

        $this->assertCount(6, $response['headers']);
        $this->assertEquals($dummyResponseHeader, $response['headers']);
        $this->assertEquals($dummyContent, $response['content'][0]);
    }
}
