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
 * This object is used to store a collection of queueEntry Items.
 *
 * {@inheritdoc}
 *
 * class.tx_crawler_domain_queueEntryCollection.php
 *
 * @subject tx_crawler_domain_queue_entry
 * @author Timo Schmidt <schmidt@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: class.tx_crawler_domain_queue_entryCollection.php $
 * @date 20.05.2008 11:38:21
 * @see ArrayObject
 * @category database
 * @package TYPO3
 * @subpackage crawler
 * @access public
 */
class tx_crawler_domain_queue_entryCollection extends ArrayObject {

	/**
	* Method to retrieve an element from the collection.
	* @access public
 	* @throws tx_mvc_exception_argumentOutOfRange
	* @return tx_crawler_domain_queue_entry
	*/
	public function offsetGet($index) {
		if (! parent::offsetExists($index)) {
			throw new tx_mvc_exception_argumentOutOfRange('Index "' . var_export($index, true) . '" for tx_crawler_domain_queue_entry are not available');
		}
		return parent::offsetGet($index);
	}

	/**
	* Mehtod to add an element to the collection-
	*
	* @param mixed $index
	* @param tx_crawler_domain_queue_entry $subject
	* @throws InvalidArgumentException
	* @return void
	*/
	public function offsetSet($index, $subject) {
		if (! $subject instanceof tx_crawler_domain_queueEntry ) {
			throw new InvalidArgumentException('Wrong parameter type given, "tx_crawler_domain_queue_entry" expected!');
		}
		
		parent::offsetSet($index, $subject);
	}

	/**
	* Method to append an element to the collection
	* @param tx_crawler_domain_queue_entry $subject
	* @throws InvalidArgumentException
	* @return void
	*/
	public function append($subject) {
		if (! $subject instanceof tx_crawler_domain_queueEntry ) {
			throw new InvalidArgumentException('Wrong parameter type given, "tx_crawler_domain_queue_entry" expected!');
		}
		
		parent::append($subject);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/queue/class.tx_crawler_domain_queue_entryCollection.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/queue/class.tx_crawler_domain_queue_entryCollection.php']);
}
?>