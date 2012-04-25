<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2010, AOE media GmbH <dev@aoemedia.de>
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

require_once t3lib_extMgm::extPath('crawler') . 'class.tx_crawler_lib.php';

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
		$db = $this->useTestDatabase();
		$this->importStdDB();

		// order of extension-loading is important !!!!
		$this->importExtensions(array('cms', 'crawler'));

		$this->crawlerLibrary = $this->getMock('tx_crawler_lib', array('buildRequestHeaderArray', 'executeShellCommand'));
	}

	/**
	* Resets the test enviroment after the test.
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
	 * Tests whethe the makeDirectRequest feature works properly.
	 *
	 * @test
	 */
	public function isRequestUrlWithMakeDirectRequestsProcessedCorrectlyWithoutDefinedBasePath() {
		$extensionSettings = array(
			'makeDirectRequests' => 1,
			'frontendBasePath' => '',
			'phpPath' => 'PHPPATH',
		);
		$this->crawlerLibrary->setExtensionSettings($extensionSettings);

		$testUrl = 'http://localhost/' . uniqid();
		$testHeader = 'X-Test: ' . uniqid();
		$testHeaderArray = array($testHeader);
		$testCrawlerId = 13;
		$testContent = uniqid('Content');
		$frontendBasePath = t3lib_div::getIndpEnv('TYPO3_SITE_PATH');

		$expectedCommand = escapeshellcmd('PHPPATH') . ' ' .
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
	 * Tests whethe the makeDirectRequest feature works properly.
	 *
	 * @test
	 */
	public function isRequestUrlWithMakeDirectRequestsProcessedCorrectlyWithDefinedBasePath() {
		$extensionSettings = array(
			'makeDirectRequests' => 1,
			'frontendBasePath' => '/cms/',
			'phpPath' => 'PHPPATH',
		);
		$this->crawlerLibrary->setExtensionSettings($extensionSettings);

		$testUrl = 'http://localhost/' . uniqid();
		$testHeader = 'X-Test: ' . uniqid();
		$testHeaderArray = array($testHeader);
		$testCrawlerId = 13;
		$testContent = uniqid('Content');
		$frontendBasePath = '/cms/';

		$expectedCommand = escapeshellcmd('PHPPATH') . ' ' .
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
}

?>