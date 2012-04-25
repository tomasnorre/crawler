<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Fabrizio Branca (fabrizio.branca@aoemedia.de)
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
 * Context menu processing
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @package TYPO3
 * @subpackage crawler
 */
class tx_crawler_contextMenu {

	/**
	 * Main function
	 *
	 * @param clickMenu reference parent object
	 * @param array menutitems for manipultation
	 * @param string table name
	 * @param int uid
	 * @return array manipulated menuitems
	 */
	function main(clickMenu $backRef, array $menuItems, $table, $uid) {

		if ($table != 'tx_crawler_configuration') {
				// early return without doing anything
			return $menuItems;
		}

		$localItems = array();

		$row = t3lib_BEfunc::getRecord($table, $uid, 'pid, name, processing_instruction_filter', '', true);

		if (!empty($row)) {

			if (version_compare(TYPO3_version,'4.5.0','>=')) {
				$url = t3lib_extMgm::extRelPath('info') . 'mod1/index.php';
			} else {
				$url  = $backRef->backPath . 'mod/web/info/index.php';
			}
			
			$url .= '?id=' . intval($row['pid']);
			$url .= '&SET[function]=tx_crawler_modfunc1';
			$url .= '&SET[crawlaction]=start';
			$url .= '&configurationSelection[]=' . $row['name'];

			foreach (t3lib_div::trimExplode(',', $row['processing_instruction_filter']) as $processing_instruction) {
				$url .= '&procInstructions[]=' . $processing_instruction;
			}

			// $onClick = $backRef->urlRefForCM($url);
			$onClick = "top.nextLoadModuleUrl='".$url."';top.goToModule('web_info',1);";

			$localItems[] = $backRef->linkItem(
				'Crawl',
				$backRef->excludeIcon('<img src="'.$backRef->backPath . t3lib_extMgm::extRelPath('crawler').'icon_tx_crawler_configuration.gif" border="0" align="top" alt="" />'),
				$onClick,
				0
			);

		}
		return array_merge($menuItems, $localItems);
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_contextMenu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_contextMenu.php']);
}

?>