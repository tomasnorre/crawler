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

require_once t3lib_extMgm::extPath('crawler') . 'domain/queue/class.tx_crawler_domain_queue_entryRepository.php';

/**
 * Testclass to test the queue entry repository
 *
 * {@inheritdoc}
 *
 * class.tx_crawler_domain_queueEntryRepository.php
 *
 * @author Timo Schmidt <schmidt@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: class.tx_crawler_domain_queueEntryRepository.php $
 * @date 20.05.2008 10:40:40
 * @seetx_phpunit_database_testcase
 * @category testcase
 * @package TYPO3
 * @subpackage crawler
 * @access public
 */
 
class tx_crawler_domain_queue_entryRepository_testcase extends tx_phpunit_database_testcase {	
	/**
	* This method overwrites the method of the baseclass to ensure that no live database will be used.
	*
	*/
	
	/**
	 * Holds an instance of the queueEntry repository
	 *
	 * @var tx_crawler_domain_queue_entryRepository
	 */
	protected $queueRepositoryMock;
	
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
		$this->importExtensions(array('crawler','cc_devlog'));
		
		$oneday 	= 24 * 60 * 60;
		$now		= $oneday * 4;
		
		$this->queueRepositoryMock  = $this->getMock('tx_crawler_domain_queue_entryRepository',array('getCurrentTimestamp'),array());
		//overwrite the method for time determination to get a testable repository
		$this->queueRepositoryMock->expects($this->any())->method('getCurrentTimestamp')->will($this->returnValue($now));
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
	* This testcase is used to test that old queue entrys can be purged.
	* 
	* @test
	* @author Timo Schmidt <timo.schmidt@aoemedia.de>
	* @param void
	* @return void
	*/
	public function canDeleteOldQueueEntrys(){
		$this->importDataSet ( '/fixtures/canDeleteOldQueueEntrys/tx_crawler_queue.xml' );

		//here we should get all fixture items because none was deleted		
		$allQueueItems 		= $this->queueRepositoryMock->findAll();
		$this->assertEquals($allQueueItems->count(),4);
		
		//purge all which are older then 4 days.
		 $this->queueRepositoryMock->deleteAllExecutedAndOlderThan(1);
		
		$allQueueItems		=  $this->queueRepositoryMock->findAll();

		$this->assertEquals($allQueueItems->count(),2);
	}
	
	/**
	 * This method should test thagt all processable items of
	 * the queue can be found.
	 * 
	 * @test
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param void
	 * @return void
	 *
	 */
	public function canFindAllItemsWhichNeedToBeProcessed(){
		$this->importDataSet ( '/fixtures/canFindAllItemsWhichNeedToBeProcessed/tx_crawler_queue.xml' );
		
		$limit = 10;
		$itemsToProcess = $this->queueRepositoryMock->findItemsToProcess($limit);
		$this->assertEquals($itemsToProcess->count(),2,'Wrong number of items to process');
	}
}

?>