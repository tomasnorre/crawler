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

require_once t3lib_extMgm::extPath('crawler') . 'domain/infopot/class.tx_crawler_domain_infopot_entry.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/infopot/class.tx_crawler_domain_infopot_entryCollection.php';


/**
 * This repository is used to query for infopot objects.
 *
 * class.tx_crawler_domain_infopot_entryRepository.php
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @subject tx_crawler_domain_infopot_entryRepository
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
class tx_crawler_domain_infopot_entryRepository extends tx_mvc_ddd_abstractRepository {

	/**
	* @var string The name of the objectclass for that this repository s responsible
	*/
	protected $objectClassName = 'tx_crawler_domain_infopot_entry';

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/path//crawler/domain/queue/class.tx_crawler_domain_queue_entryRepository.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/path//crawler/domain/queue/class.tx_crawler_domain_queue_entryRepository.php']);
}

?>