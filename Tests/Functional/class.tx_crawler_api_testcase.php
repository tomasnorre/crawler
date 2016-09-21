<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

/**
 * This test is used to test that the crawler api works correct
 *
 * {@inheritdoc}
 *
 * class.tx_crawler_api_testcase.php
 *
 * @author Timo Schmidt <schmidt@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: class.tx_crawler_api_testcase.php $
 * @date 16.06.2009 16:55:54
 * @seetx_phpunit_database_testcase
 * @category testcase
 * @package TYPO3
 * @subpackage crawler
 * @access public
 */
class tx_crawler_api_testcase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	* @var array
	*/
	protected $coreExtensionsToLoad = array('cms', 'core', 'frontend', 'version', 'lang', 'extensionmanager');

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array('typo3conf/ext/crawler');

	/**
	 *
	 * @var array stores the old rootline
	 */
	protected $oldRootline;

	/**
	* Creates the test environment.
	*
	*/
	function setUp() {
		parent::setUp();

		//restore old rootline
		$this->oldRootline =   $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'];
		//clear rootline
		$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:20:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:2:"20";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"9";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"1";s:7:"phpPath";s:16:"/usr/bin/php5 -q";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"1";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";s:21:"PageUidRootTypoScript";s:1:"1";}';

		$this->importDataSet(dirname(__FILE__).'/data/pages.xml');
		$this->importDataSet(dirname(__FILE__).'/data/sys_template.xml');

	}

	/**
	* Resets the test enviroment after the test.
	*/
	function tearDown() {
		parent::tearDown();
   		//restore rootline
   		$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] = $this->oldRootline;
	}

	/**
	 * This test is used to check that the api will not create duplicate entries for
	 * two pages which should both be crawled in the past, because it is only needed one times.
	 * The testcase uses a TSConfig crawler configuration.
	 *
	 * @test
	 * @param void
	 * @author Timo Schmidt
	 * @author Fabrizio Branca
	 * @return void
	 */
	public function canNotCreateDuplicateQueueEntriesForTwoPagesInThePast() {
		$this->importDataSet(dirname(__FILE__).'/data/canNotAddDuplicatePagesToQueue.xml');

		$crawler_api = $this->getMockedCrawlerAPI(100000);

		$crawler_api->addPageToQueueTimed(5,9998);
		$crawler_api->addPageToQueueTimed(5,3422);

		$this->assertEquals($crawler_api->countUnprocessedItems(),1);
	}

	/**
	 * This test should check that the api does not create two queue entries for
	 * two pages which should be crawled at the same time in the future.
	 * The testcase uses a TSConfig crawler configuration.
	 *
	 * @test
	 * @param void
	 * @author Timo Schmidt
	 * @author Fabrizio Branca
	 * @return void
	 *
	 */
	public function canNotCreateDuplicateForTwoPagesInTheFutureWithTheSameTimestamp() {
		$this->importDataSet(dirname(__FILE__).'/data/canNotAddDuplicatePagesToQueue.xml');

		$crawler_api = $this->getMockedCrawlerAPI(100000);

		$crawler_api->addPageToQueueTimed(5,100001);
		$crawler_api->addPageToQueueTimed(5,100001);

		$this->assertEquals($crawler_api->countUnprocessedItems(),1);
	}

	/**
	 * This test is used to check that the api can be used to schedule one  page two times
	 * for a diffrent timestamp in the future.
	 * The testcase uses a TSConfig crawler configuration.
	 *
	 * @test
	 * @param void
	 * @author Timo Schmidt
	 * @author Fabrizio Branca
	 * @return void
	 */
	public function canCreateTwoQueueEntriesForDiffrentTimestampsInTheFuture() {
		$this->importDataSet(dirname(__FILE__).'/data/canNotAddDuplicatePagesToQueue.xml');

		$crawler_api = $this->getMockedCrawlerAPI(100000);

		$crawler_api->addPageToQueueTimed(5,100011);
		$crawler_api->addPageToQueueTimed(5,100014);

		$this->assertEquals($crawler_api->countUnprocessedItems(),2);
	}

	/**
	 * This testcase is used to check that pages can be queued in an environment.
	 * Where the crawler is configured using configuration records instead of pagets config.
	 *
	 * @test
	 * @param void
	 * @author Timo Schmidt
	 * @return void
	 */
	public function canCreateQueueEntrysUsingConfigurationRecord() {
		$this->importDataSet(dirname(__FILE__).'/data/canCreateQueueEntrysUsingConfigurationRecord.xml');
		$crawler_api = $this->getMockedCrawlerAPI(100000);
		$crawler_api->addPageToQueueTimed(7,100011);
		$crawler_api->addPageToQueueTimed(7,100059);

		$queueItems = $crawler_api->getUnprocessedItems();
		$assertedParameter = 'a:3:{s:3:"url";s:49:"http://www.testcase.de/index.php?id=7&L=0&S=CRAWL";s:16:"procInstructions";a:1:{i:0;s:20:"tx_staticpub_publish";}s:15:"procInstrParams";a:1:{s:21:"tx_staticpub_publish.";a:1:{s:16:"includeResources";s:7:"relPath";}}}';

		$this->assertEquals($queueItems[0]['page_id'],7);
		$this->assertEquals($queueItems[0]['scheduled'],100011);
		$this->assertEquals($queueItems[0]['parameters'],$assertedParameter,'Wrong queue parameters created by crawler lib for configuration record');


		$assertedParameter = 'a:3:{s:3:"url";s:49:"http://www.testcase.de/index.php?id=7&L=0&S=CRAWL";s:16:"procInstructions";a:1:{i:0;s:20:"tx_staticpub_publish";}s:15:"procInstrParams";a:1:{s:21:"tx_staticpub_publish.";a:1:{s:16:"includeResources";s:7:"relPath";}}}';
		$this->assertEquals($queueItems[1]['page_id'],7);
		$this->assertEquals($queueItems[1]['scheduled'],100059);
		$this->assertEquals($queueItems[1]['parameters'],$assertedParameter,'Wrong queue parameters created by crawler lib for configuration record');

		$this->assertEquals($crawler_api->countUnprocessedItems(),2,'Could not add pages to queue configured by record');
	}

	/**
	 * Creates a mocked crawler api with a faked current time state
	 *
	 * @param int $currentTime
	 * @return tx_crawler_api
	 */
	protected function getMockedCrawlerAPI($currentTime) {
			//created mocked crawler lib which returns a faked timestamp
		$crawler_lib = $this->getMock('tx_crawler_lib', array('getCurrentTime', 'drawURLs_PIfilter'));
		$crawler_lib->expects($this->any())->method("getCurrentTime")->will($this->returnValue($currentTime));
		$crawler_lib->expects($this->any())->method("drawURLs_PIfilter")->will($this->returnValue(TRUE));

		/* @var $crawler_api tx_crawler_api */
		//create mocked api
		$crawler_api = $this->getMock('tx_crawler_api',array('findCrawler'));
		$crawler_api->expects($this->any())->method("findCrawler")->will($this->returnValue($crawler_lib));

		return $crawler_api;
	}

	/**
	 * @test
	 */
	public function canReadHttpResponseFromStream() {
		require_once __DIR__ . '/../Unit/proxies/class.tx_crawler_lib_proxy.php';

		$dummyContent = 'Lorem ipsum';
		$dummyResponseHeader =  array(
			'HTTP/1.1 301 Moved Permanently',
			'Server: nginx',
			'Date: Fri, 25 Apr 2014 08:26:15 GMT',
			'Content-Type: text/html',
			'Content-Length: 11',
			'Connection: close'
		);
		$dummyServerResponse = array_merge($dummyResponseHeader, array('', $dummyContent));

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
