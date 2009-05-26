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
 * Collection of crawler configuration objects
 *
 * {@inheritdoc}
 *
 * class.tx_crawler_domain_configuration_configurationCollection.php
 *
 * @subject tx_crawler_domain_configuration_configuration
 * @author Timo Schmidt <schmidt@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: class.tx_crawler_domain_configuration_configurationCollection.php $
 * @date 26.05.2008 17:09:16
 * @see ArrayObject
 * @category database
 * @package TYPO3
 * @subpackage crawler
 * @access public
 */
class tx_crawler_domain_configuration_configurationCollection extends ArrayObject {

	/**
	* Method to retrieve an element from the collection.
	* @access public
 	* @throws tx_mvc_exception_argumentOutOfRange
	* @return tx_crawler_domain_configuration_configuration
	*/
	public function offsetGet($index) {
		if (! parent::offsetExists($index)) {
			throw new tx_mvc_exception_argumentOutOfRange('Index "' . var_export($index, true) . '" for tx_crawler_domain_configuration_configuration are not available');
		}
		return parent::offsetGet($index);
	}

	/**
	* Mehtod to add an element to the collection-
	*
	* @param mixed $index
	* @param tx_crawler_domain_configuration_configuration $subject
	* @throws InvalidArgumentException
	* @return void
	*/
	public function offsetSet($index, $subject) {
		if (! $subject instanceof tx_crawler_domain_configuration_configuration ) {
			throw new InvalidArgumentException('Wrong parameter type given, "tx_crawler_domain_configuration_configuration" expected!');
		}
		
		parent::offsetSet($index, $subject);
	}

	/**
	* Method to append an element to the collection
	* @param tx_crawler_domain_configuration_configuration $subject
	* @throws InvalidArgumentException
	* @return void
	*/
	public function append($subject) {
		if (! $subject instanceof tx_crawler_domain_configuration_configuration ) {
			throw new InvalidArgumentException('Wrong parameter type given, "tx_crawler_domain_configuration_configuration" expected!');
		}
		
		parent::append($subject);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/configuration/class.tx_crawler_domain_configuration_configurationCollection.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/configuration/class.tx_crawler_domain_configuration_configurationCollection.php']);
}
?>