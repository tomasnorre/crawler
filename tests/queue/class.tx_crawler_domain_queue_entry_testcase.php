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
 * A queue entry represents on job in the queue. This testcase should
 * ensure, that queue entrys can be handled correctly
 *
 * {@inheritdoc}
 *
 * class.tx_crawler_domain_queueEntry_testcase.php
 *
 * @author Timo Schmidt <schmidt@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: class.tx_crawler_domain_queueEntry_testcase.php $
 * @date 20.05.2008 10:06:56
 * @seetx_phpunit_database_testcase
 * @category testcase
 * @package TYPO3
 * @subpackage crawler
 * @access public
 */
 
require_once t3lib_extMgm::extPath('crawler') . 'domain/configuration/class.tx_crawler_domain_configuration_configuration.php';


class tx_crawler_domain_queueEntry_testcase extends tx_phpunit_database_testcase {	
	
	/**
	* This method overwrites the method of the baseclass to ensure that no live database will be used.
	*
	*/
	protected function useTestDatabase($databaseName = null) {
		$db = $GLOBALS ['TYPO3_DB'];
		if ($databaseName) {
			$database = $databaseName;
		} else {
			$database = $this->testDatabase;
		}
		
		if (! $db->sql_select_db ( $database )) {
			die ( "Test Database not available" );
		}
		return $db;
	}

	/**
	* Creates the test environment.
	*
	*/
	function setUp() {
		$this->createDatabase();
		$db = $this->useTestDatabase();
		
		// order of extension-loading is important !!!!
		$this->importExtensions(array('corefake','crawler'));
	}

	/**
	* Resets the test enviroment after the test.
	*/
	function tearDown() {
		$this->cleanDatabase();
   		$this->dropDatabase();
   		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
	}

	/**
	 * Overwrites the importDataSet method 
	 * 
	 * @param string path to import
	 */
	protected function importDataSet($path){
		parent::importDataSet(  dirname ( __FILE__ ) . $path);	
	}	

	/**
	 * This testcase should be used to test, that an queueEntry object creates the correct urls
	 * to be crawled by the crawler.
	 * 
	 * @test
	 * @param void
	 * @return void
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 *
	 */
	public function canGetUrlsFromQueueEntry(){
		$fixtureCrawlerConfiguration = new tx_crawler_domain_configuration_configuration();
		$fixtureCrawlerConfiguration->setName('staticpub');
		$fixtureCrawlerConfiguration->setConfiguration('&S=CRAWL&L=[4|5]');
		$fixtureCrawlerConfiguration->setBase_url('http://www.testcase.de');
		$fixtureCrawlerConfiguration->setPidsonly('');
		$fixtureCrawlerConfiguration->setProcessing_instruction_filter('tx_staticpub_publish,tx_cachemgm_recache');

		//
		$queueEntryMock 			 = $this->getMock('tx_crawler_domain_queue_entry',array('getConfigurationObject'),array());
		$queueEntryMock->setPageid(4711);
		$queueEntryMock->expects($this->any())->method('getConfigurationObject')->will($this->returnValue($fixtureCrawlerConfiguration));
		
		$URLs = $queueEntryMock->getUrls();
		
		$this->assertEquals($URLs[0],'?id=4711&L=4&S=CRAWL');
		$this->assertEquals($URLs[1],'?id=4711&L=5&S=CRAWL');
	}
	
	/**
	 * This testcase should be used to test, that a queue entry record can also determine his
	 * configuration from page ts config.
	 * 
	 * @test
	 * @param void
	 * @return void
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 */
	public function canGetCrawlerConfigurationFromPageTSConfig(){
		$this->importDataSet('/fixtures/canGetCrawlerConfigurationFromPageTSConfig/pages.xml');
		$queueEntry = new tx_crawler_domain_queue_entry();
		$queueEntry->setPageid(4711);
		//needed to determine the correct subconfiguration from pagets config
		$queueEntry->setConfiguration_id('staticpub');
		
		$URLs 		= $queueEntry->getUrls();
		
		$this->assertEquals($URLs[0],'?id=4711&L=4&S=CRAWL');
		$this->assertEquals($URLs[1],'?id=4711&L=5&S=CRAWL');		
		
	}
}

?>