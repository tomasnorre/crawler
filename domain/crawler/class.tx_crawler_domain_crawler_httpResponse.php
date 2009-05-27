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
 * Http response
 *
 * @author	Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: $
 * @since 2009-05-27
 * @package TYPO3
 * @subpackage crawler
 */
class tx_crawler_domain_crawler_httpResponse {

	/**
	 * @var string header
	 */
	protected $header;

	/**
	 * @var string content
	 */
	protected $content;

	public function getHeader() {
		return $this->header;
	}

	public function getContent() {
		return $this->content;
	}

	/**
	 * Set the header
	 *
	 * @param string header
	 * @return tx_crawler_domain_crawler_httpResponse
	 */
	public function setHeader($header) {
		$this->header = $header;
		return $this;
	}

	/**
	 * Set the content
	 *
	 * @param string content
	 * @return tx_crawler_domain_crawler_httpResponse
	 */
	public function setContent($content) {
		$this->content = $content;
		return $this;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/crawler/class.tx_crawler_domain_crawler_httpResponse.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/crawler/class.tx_crawler_domain_crawler_httpResponse.php']);
}

?>