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
 * Class with tca functions
 *
 * @author	Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: $
 * @since 2009-05-28
 * @package TYPO3
 * @subpackage crawler
 */
class tx_crawler_tcaFunc {

	/**
	 * Get crawler processing instructions.
	 * This function is called as a itemsProcFunc in tx_crawler_configuration.processing_instruction_filter
	 *
	 * @param array configuration
	 * @return array configuration
	 */
	public function getProcessingInstructions(array $config) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] as $key => $value) {
				$config['items'][] = array($value.' ['.$key.']', $key, $this->getExtensionIcon($key));
			}
		}
		return $config;
	}

	/**
	 * Get path to ext_icon.gif from processing instruction key
	 *
	 * @param string $key Like tx_realurl_rebuild
	 * @return string
	 */
	protected function getExtensionIcon($key) {
		$extIcon = '';

		if (method_exists(t3lib_extMgm, 'getExtensionKeyByPrefix')) {
			$parts = explode('_', $key);
			if (is_array($parts) && count($parts) > 2) {
				$extensionKey = t3lib_extMgm::getExtensionKeyByPrefix('tx_' . $parts[1]);
				$extIcon = t3lib_extMgm::extRelPath($extensionKey) . 'ext_icon.gif';
			}
		}

		return $extIcon;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_tcaFunc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_tcaFunc.php']);
}

?>