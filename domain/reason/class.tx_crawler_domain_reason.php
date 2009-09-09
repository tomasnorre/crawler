<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 AOE media (dev@aoemedia.de)
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

require_once t3lib_extMgm::extPath('crawler') . 'domain/lib/class.tx_crawler_domain_lib_abstract_dbobject.php';

class tx_crawler_domain_reason extends tx_crawler_domain_lib_abstract_dbobject {

	protected static $tableName = 'tx_crawler_reason';

	/**
	 * THE CONSTANTS REPRESENT THE KIND OF THE REASON
	 *
	 * Convention for own states: <extensionkey>_<reason>
	 */
	const REASON_DEFAULT = 'crawler_default_reason';
	const REASON_GUI_SUBMIT = 'crawler_gui_submit_reason';
	const REASON_CLI_SUBMIT = 'crawler_cli_submit_reason';

	/**
	 * Set uid
	 *
	 * @param int uid
	 * @return void
	 */
	public function setUid($uid) {
		$this->row['uid'] = $uid;
	}

	/**
	 * Method to set a timestamp for the creation time of this record
	 *
	 * @param int $time
	 */
	public function setCreationDate($time) {
		$this->row['crdate'] = $time;
	}

	/**
	 * This method can be used to set a user id of the user who has created this reason entry
	 *
	 * @param int $user_id
	 */
	public function setBackendUserId($user_id) {
		$this->row['cruser_id'] = $user_id;
	}

	/**
	 * Method to set the type of the reason for this reason instance (see constances)
	 *
	 * @param string $string
	 */
	public function setReason($string) {
		$this->row['reason'] = $string;
	}

	/**
	 * This method returns the attached reason text.
	 * @return string
	 */
	public function getReason() {
		return $this->row['reason'];
	}

	/**
	 * This method can be used to assign a detail text to the crawler reason
	 *
	 * @param string $detail_text
	 */
	public function setDetailText($detail_text) {
		$this->row['detail_text'] = $detail_text;
	}

	/**
	 * Returns the attachet detail text.
	 *
	 * @param void
	 * @return string
	 */
	public function getDetailText() {
		return $this->row['detail_text'];
	}

	/**
	 * This method is used to set the uid of the queue entry
	 * where the reason is relevant for.
	 *
	 * @param int $entry_id
	 */
	public function setQueueEntryUid($entry_uid) {
		$this->row['queue_entry_uid'] = $entry_uid;
	}

}

?>