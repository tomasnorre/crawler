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

require_once t3lib_extMgm::extPath('crawler') . 'domain/queue/class.tx_crawler_domain_queue_entry.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/queue/class.tx_crawler_domain_queue_entryCollection.php';


/**
 * This repository is used to query for queue objects.
 *
 * {@inheritdoc}
 *
 * class.tx_crawler_domain_queueEntryRepository.php
 *
 * @author Timo Schmidt <schmidt@aoemedia.de>
 * @subject tx_crawler_domain_queueEntry
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: class.tx_crawler_domain_queueEntryRepository.php $
 * @date 20.05.2008 11:40:23
 * @see tx_mvc_ddd_abstractRepository
 * @category database
 * @package TYPO3
 * @subpackage crawler
 * @access public
 */
class tx_crawler_domain_queue_entryRepository extends tx_mvc_ddd_abstractRepository {
	/**
	* Must be set!
	* The name of the objectclass for that this repository s responsible
	*
	* @var string
	*/
	protected $objectClassName = 'tx_crawler_domain_queue_entry';
	
	
	/**
	 * Helpermethod to get the current timestamp. Used to
	 * query items based on the system time
	 * 
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param void
	 * @return int
	 */
	protected function getCurrentTimestamp(){
		return time();
	}
	
	/**
	 * This method returns a queueEntry collection
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param void
	 * @return tx_crawler_domain_queue_entryCollection
	 */
	public function findAll(){
		$arrayObject = parent::findAll();

		return $this->buildQueueEntryCollectionFromArrayObject($arrayObject);
	}
	
	/**
	 * Deletes all processed items which are x days old.
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param int $int number of days
	 */
	public function deleteAllExecutedAndOlderThan($numDays){
		$deleteDate 	= $this->getCurrentTimestamp() - 24 * 60 * 60 * intval($numDays);
		
		$del 			= $this->getDatabase()->exec_DELETEquery(
			'tx_crawler_queue',
			'exec_time!=0 AND exec_time<' .$deleteDate
		);	
		
		return $del;
	}
	
	/**
	 * This method retrieves a collection 
	 *
	 * @author Timo Schmidt
	 * @param int $limit
	 * @return tx_crawler_domain_queue_entryCollection
	 */
	public function findItemsToProcess($limit){
		$where	 	= 'exec_time=0 AND process_scheduled= 0 AND scheduled<='.intval($this->getCurrentTimestamp());
		$limit 		= tx_mvc_filter_factory::getIntGreaterThanFilter()->filter($limit);
		$orderby	= 'scheduled, qid';
		$arrayObjects = $this->findByWhere($where,true,$orderby,'',$limit);
		
		return $this->buildQueueEntryCollectionFromArrayObject($arrayObjects);
	}
	
	/**
	 * Helpermethod to create a queueEntryCollection from an ArrayObject
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param ArrayObject $ArrayObject
	 * @return tx_crawler_domain_queue_entryCollection
	 */
	protected function buildQueueEntryCollectionFromArrayObject(ArrayObject  $ArrayObject){
		$queueEntryCollection = new tx_crawler_domain_queue_entryCollection($ArrayObject->getArrayCopy());
		return $queueEntryCollection;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/path//crawler/domain/queue/class.tx_crawler_domain_queue_entryRepository.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/path//crawler/domain/queue/class.tx_crawler_domain_queue_entryRepository.php']);
}

?>