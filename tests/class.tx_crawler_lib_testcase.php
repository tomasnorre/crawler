<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2010, AOE GmbH <dev@aoe.com>
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
 * This test is used to test that the public interface of the library works correctly
 *
 * @see tx_phpunit_database_testcase
 * @category testcase
 * @package TYPO3
 * @subpackage crawler
 */
class tx_crawler_lib_testcase extends tx_phpunit_database_testcase {

	/**
	 * @var tx_crawler_lib
	 */
	protected $crawlerLibrary;

	/**
	* Creates the test environment.
	*
	* @return void
	*/
	public function setUp() {
		$this->createDatabase();
		$this->useTestDatabase();
		$this->importStdDB();

			// order of extension-loading is important !!!!
		$this->importExtensions(array('cms','crawler'));

		$this->crawlerLibrary = $this->getMock('tx_crawler_lib', array('buildRequestHeaderArray', 'executeShellCommand'));
	}

	/**
	* Resets the test environment after the test.
	*
	* @return void
	*/
	public function tearDown() {
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);

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
							escapeshellarg(t3lib_extMgm::extPath('crawler').'cli/bootstrap.php') . ' ' .
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
							escapeshellarg(t3lib_extMgm::extPath('crawler').'cli/bootstrap.php') . ' ' .
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
	 * @test
	 */
	public function canReadHttpResponseFromStream() {
		require_once __DIR__ . '/proxies/class.tx_crawler_lib_proxy.php';

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

		$crawlerLibrary = new tx_crawler_lib_proxy();
		$response = $crawlerLibrary->getHttpResponseFromStream($fp);

		$this->assertCount(6, $response['headers']);
		$this->assertEquals($dummyResponseHeader, $response['headers']);
		$this->assertEquals($dummyContent, $response['content'][0]);
	}
}
