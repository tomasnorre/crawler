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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Crawler Service
 * Collection of static methods that represent the api to the crawler
 *
 * @author	Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: $
 * @since 2009-05-27
 * @package TYPO3
 * @subpackage crawler
 */
class tx_crawler_domain_crawler_service {

	/**
	 * Get all crawler configuration for a given page uid
	 *
	 * @param int page uid
	 * @return tx_crawler_domain_configuration_configurationCollection
	 */
	public static function getCrawlerConfigurationsForPage($uid) {
		throw new tx_mvc_exception_notImplemented();
	}

	/**
	 * Determine if this url should not be crawled. For this purpose a service is processed
	 * By default there is only one service that does not exclude anything.
	 *
	 * @param string url
	 * @return bool
	 */
	public static function excludeUrl($url) {
		return false;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/crawler/class.tx_crawler_domain_crawler_service.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/crawler/class.tx_crawler_domain_crawler_service.php']);
}

?>