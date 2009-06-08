<?php
class tx_crawler_hooks_tsfe{
	/**************************
	 *
	 * tslib_fe hooks:
	 *
	 **************************/

	/**
	 * Initialization hook (called after database connection)
	 * Takes the "HTTP_X_T3CRAWLER" header and looks up queue record and verifies if the session comes from the system (by comparing hashes)
	 *
	 * @param	array		Parameters from frontend
	 * @param	object		TSFE object (reference under PHP5)
	 * @return	void
	 */
	function fe_init(&$params, $ref)	{

			// Authenticate crawler request:
		if (isset($_SERVER['HTTP_X_T3CRAWLER']))	{
			//@todo: ask service to exclude current call for special reasons: for example no relevance because the language version is not affected

			list($queueId,$hash) = explode(':', $_SERVER['HTTP_X_T3CRAWLER']);
			list($queueRec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_crawler_queue','qid='.intval($queueId));

				// If a crawler record was found and hash was matching, set it up:
			if (is_array($queueRec) && $hash === md5($queueRec['qid'].'|'.$queueRec['set_id'].'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']))	{
				$params['pObj']->applicationData['tx_crawler']['running'] = TRUE;
				$params['pObj']->applicationData['tx_crawler']['parameters'] = unserialize($queueRec['parameters']);
				$params['pObj']->applicationData['tx_crawler']['log'] = array();
			} else {
				die('No crawler entry found!');
			}
		}
	}

	/**
	 * Initialization of FE-user, setting the user-group list if applicable.
	 *
	 * @param	array		Parameters from frontend
	 * @param	object		TSFE object
	 * @return	void
	 */
	function fe_feuserInit(&$params, $ref)	{
		if ($params['pObj']->applicationData['tx_crawler']['running'])	{
			$grList = $params['pObj']->applicationData['tx_crawler']['parameters']['feUserGroupList'];
			if ($grList)	{
				if (!is_array($params['pObj']->fe_user->user))	$params['pObj']->fe_user->user = array();
				$params['pObj']->fe_user->user['usergroup'] = $grList;
				$params['pObj']->applicationData['tx_crawler']['log'][] = 'User Groups: '.$grList;
			}
		}
	}

	/**
	 * Whether to output rendered content or not. If the crawler is running, the rendered output is never outputted!
	 *
	 * @param	array		Parameters from frontend
	 * @param	object		TSFE object
	 * @return	void
	 */
	function fe_isOutputting(&$params, $ref)	{
		if ($params['pObj']->applicationData['tx_crawler']['running'])	{
			$params['enableOutput'] = FALSE;
		}
	}

	/**
	 * Concluding: Outputting serialized information instead of letting rendered content out.
	 *
	 * @param	array		Parameters from frontend
	 * @param	object		TSFE object
	 * @return	void
	 */
	function fe_eofe(&$params, $ref)	{
		if ($params['pObj']->applicationData['tx_crawler']['running'])	{
			$params['pObj']->applicationData['tx_crawler']['vars'] = array(
				'id' => $params['pObj']->id,
				'gr_list' => $params['pObj']->gr_list,
				'no_cache' => $params['pObj']->no_cache,
			);

				/**
				 * Required because some extensions (staticpub) might never be requested to run due to some Core side effects
				 * and since this is considered as error the crawler should handle it properly
				 */
				if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'] as $pollable) {
						if(empty($params['pObj']->applicationData['tx_crawler']['success'][$pollable])) {
							$params['pObj']->applicationData['tx_crawler']['errorlog'][] = 'Error: Pollable extension ('.$pollable.') did not complete successfully.';
						}
					}
				}

				// Output log data for crawler (serialized content):
				$str = serialize($params['pObj']->applicationData['tx_crawler']);
				header('Content-Length: '.strlen($str));
				echo $str;
                // Exit since we don't want anymore output!
			exit;
		}
	}
}
?>