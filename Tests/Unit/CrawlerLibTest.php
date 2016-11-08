<?php
namespace AOE\Crawler\Tests;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
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

use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class CrawlerLibTest
 *
 * @package AOE\Crawler\Tests
 */
class CrawlerLibTest extends UnitTestCase {
	/**
	 * @var \tx_crawler_lib
	 */
	protected $crawlerLibrary;
	
	/**
	 * Creates the test environment.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->crawlerLibrary = $this->getMock('\tx_crawler_lib', array('buildRequestHeaderArray', 'executeShellCommand'), array(), '', FALSE);
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler'] = 'a:20:{s:9:"sleepTime";s:4:"1000";s:16:"sleepAfterFinish";s:2:"10";s:11:"countInARun";s:2:"20";s:14:"purgeQueueDays";s:2:"14";s:12:"processLimit";s:1:"9";s:17:"processMaxRunTime";s:3:"300";s:14:"maxCompileUrls";s:5:"10000";s:12:"processDebug";s:1:"0";s:14:"processVerbose";s:1:"0";s:16:"crawlHiddenPages";s:1:"1";s:7:"phpPath";s:16:"/usr/bin/php5 -q";s:14:"enableTimeslot";s:1:"1";s:11:"logFileName";s:0:"";s:9:"follow30x";s:1:"0";s:18:"makeDirectRequests";s:1:"1";s:16:"frontendBasePath";s:1:"/";s:22:"cleanUpOldQueueEntries";s:1:"1";s:19:"cleanUpProcessedAge";s:1:"2";s:19:"cleanUpScheduledAge";s:1:"7";s:21:"PageUidRootTypoScript";s:1:"1";}';
	}
	
	/**
	 * Resets the test environment after the test.
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->crawlerLibrary);
	}
	
	/**
	 * Tests whether the makeDirectRequest feature works properly.
	 *
	 * @test
	 */
	public function isRequestUrlWithMakeDirectRequestsProcessedCorrectlyWithoutDefinedBasePath() {
		$this->crawlerLibrary->setExtensionSettings(array(
			'makeDirectRequests' => 1,
			'frontendBasePath' => '',
			'phpPath' => 'PHPPATH',
		));
		
		$testUrl = 'http://localhost/' . uniqid();
		$testHeader = 'X-Test: ' . uniqid();
		$testHeaderArray = array($testHeader);
		$testCrawlerId = 13;
		$testContent = uniqid('Content');
		$frontendBasePath = '/';
		
		$expectedCommand =  escapeshellcmd('PHPPATH') . ' ' .
		                    escapeshellarg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler').'cli/bootstrap.php') . ' ' .
		                    escapeshellarg($frontendBasePath) . ' ' .
		                    escapeshellarg($testUrl) . ' ' .
		                    escapeshellarg(base64_encode(serialize($testHeaderArray)));
		
		$this->crawlerLibrary->expects($this->once())->method('buildRequestHeaderArray')
		                     ->will($this->returnValue($testHeaderArray));
		$this->crawlerLibrary->expects($this->once())->method('executeShellCommand')
		                     ->with($expectedCommand)->will($this->returnValue($testContent));
		
		$result = $this->crawlerLibrary->requestUrl($testUrl, $testCrawlerId);
		
		$this->assertEquals($testHeader . str_repeat("\r\n", 2), $result['request']);
		$this->assertEquals($testContent, $result['content']);
	}
	
	/**
	 * Tests whether the makeDirectRequest feature works properly.
	 *
	 * @test
	 */
	public function isRequestUrlWithMakeDirectRequestsProcessedCorrectlyWithDefinedBasePath() {
		$this->crawlerLibrary->setExtensionSettings(array(
			'makeDirectRequests' => 1,
			'frontendBasePath' => '/cms/',
			'phpPath' => 'PHPPATH',
		));
		
		$testUrl = 'http://localhost/' . uniqid();
		$testHeader = 'X-Test: ' . uniqid();
		$testHeaderArray = array($testHeader);
		$testCrawlerId = 13;
		$testContent = uniqid('Content');
		$frontendBasePath = '/cms/';
		
		$expectedCommand =  escapeshellcmd('PHPPATH') . ' ' .
		                    escapeshellarg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler').'cli/bootstrap.php') . ' ' .
		                    escapeshellarg($frontendBasePath) . ' ' .
		                    escapeshellarg($testUrl) . ' ' .
		                    escapeshellarg(base64_encode(serialize($testHeaderArray)));
		
		$this->crawlerLibrary->expects($this->once())->method('buildRequestHeaderArray')
		                     ->will($this->returnValue($testHeaderArray));
		$this->crawlerLibrary->expects($this->once())->method('executeShellCommand')
		                     ->with($expectedCommand)->will($this->returnValue($testContent));
		
		$result = $this->crawlerLibrary->requestUrl($testUrl, $testCrawlerId);
		
		$this->assertEquals($testHeader . str_repeat("\r\n", 2), $result['request']);
		$this->assertEquals($testContent, $result['content']);
	}
}