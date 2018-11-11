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
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager', 'fluid'];

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
     * Creates the test environment.
     *
     */
    public function setUp()
    {
        parent::setUp();

        //restore old rootline
        $this->oldRootline = $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'];
        //clear rootline
        $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:19:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:3:"100";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"1";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"0";s:7:"phpPath";s:12:"/usr/bin/php";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"0";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";}';

        $this->subject = new CrawlerApi();

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
     * @expectedException \InvalidArgumentException
     */
    public function isPageInQueueThrowInvalidArgumentException()
    {
        $this->subject->isPageInQueue('Cannot be interpreted as integer');
    }

    /**
     * @test
     *
     * @param $uid
     * @param $unprocessed_only
     * @param $timed_only
     * @param $timestamp
     * @param $expected
     *
     * @dataProvider isPageInQueueDataProvider
     */
    public function isPageInQueue($uid, $unprocessed_only, $timed_only, $timestamp, $expected)
    {
        $this->importDataSet(dirname(__FILE__) . '/../Fixtures/tx_crawler_queue.xml');

        $this->assertSame(
            $expected,
            $this->subject->isPageInQueue($uid, $unprocessed_only, $timed_only, $timestamp)
        );
    }

    /**
     * @test
     */
    public function isPageInQueueTimed()
    {
        $this->importDataSet(dirname(__FILE__) . '/../Fixtures/tx_crawler_queue.xml');
        $this->assertTrue($this->subject->isPageInQueueTimed(15));
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
            '4321',
            $this->subject->getLatestCrawlTimestampForPage(17)
        );
    }

    /**
     * @test
     */
    public function getCrawlHistoryForPage()
    {
        $this->importDataSet(dirname(__FILE__) . '/../Fixtures/tx_crawler_queue.xml');

        $this->assertSame(
            [
                [
                    'scheduled' => '4321',
                    'exec_time' => '20',
                    'set_id' => '0'
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
                'assignedButUnprocessed' => '3',
                'unprocessed' => '5'
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
        $this->importDataSet(dirname(__FILE__) . '/../data/canNotAddDuplicatePagesToQueue.xml');

        $crawlerApi = $this->getMockedCrawlerAPI(100000);

        $crawlerApi->addPageToQueueTimed(5, 9998);
        $crawlerApi->addPageToQueueTimed(5, 3422);

        $this->assertEquals($crawlerApi->countUnprocessedItems(), 1);
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
        $this->importDataSet(dirname(__FILE__) . '/../data/canNotAddDuplicatePagesToQueue.xml');

        $crawlerApi = $this->getMockedCrawlerAPI(100000);

        $crawlerApi->addPageToQueueTimed(5, 100001);
        $crawlerApi->addPageToQueueTimed(5, 100001);

        $this->assertEquals($crawlerApi->countUnprocessedItems(), 1);
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

        $this->assertEquals($crawlerApi->countUnprocessedItems(), 2);
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

        $queueItems = $crawlerApi->getUnprocessedItems();

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
            $crawlerApi->countUnprocessedItems(),
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
        $crawlerController = $this->createMock(CrawlerController::class, ['getCurrentTime', 'drawURLs_PIfilter']);
        $crawlerController->expects($this->any())->method("getCurrentTime")->will($this->returnValue($currentTime));
        $crawlerController->expects($this->any())->method("drawURLs_PIfilter")->will($this->returnValue(true));

        /* @var CrawlerApi $crawlerApi */
        //create mocked api
        $crawlerApi = $this->createMock(CrawlerApi::class, ['findCrawler']);
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

    /**
     * @return array
     */
    public function isPageInQueueDataProvider()
    {
        return [
            'Unprocessed Only' => [
                'uid' => 15,
                'unprocessed_only' => true,
                'timed_only' => false,
                'timestamp' => false,
                'expected' => true
            ],
            'Timed Only' => [
                'uid' => 16,
                'unprocessed_only' => false,
                'timed_only' => true,
                'timestamp' => false,
                'expected' => true
            ],
            'Timestamp Only' => [
                'uid' => 17,
                'unprocessed_only' => false,
                'timed_only' => false,
                'timestamp' => 4321,
                'expected' => true
            ],
            'Not existing page' => [
                'uid' => 40000,
                'unprocessed_only' => false,
                'timed_only' => false,
                'timestamp' => false,
                'expected' => false
            ],
        ];
    }
}
