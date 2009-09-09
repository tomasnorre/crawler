<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Tolleiv Nietsch (tolleiv.nietsch@aoemedia.de)
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
 * Url login authentification service
 */
class tx_crawler_auth extends tx_sv_authbase {

	/**
	 * Find a user by IP ('REMOTE_ADDR')
	 *
	 * @return	mixed	user array or false
	 */
	public function getUser() {
		$user = false;
		if (isset($_SERVER['HTTP_X_T3CRAWLER'])) {
			$user = $this->fetchUserRecord('_cli_crawler');
		}
		return $user;
	}

	/**
	 * Authenticate user
	 *
	 * @param array user
	 * @return int 100="don't know", 0="no", 200="yes"
	 */
	public function authUser(array $user) {
		if (isset($_SERVER['HTTP_X_T3CRAWLER']))	{
			return ($user['username']=='_cli_crawler') ? 200 : 100;
		}
		return 100;
	}

}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/aoe_wspreview/service/class.tx_aoewspreview_service_urlLogin.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/aoe_wspreview/service/class.tx_aoewspreview_service_urlLogin.php"]);
}

?>