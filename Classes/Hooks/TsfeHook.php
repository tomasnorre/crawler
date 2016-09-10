<?php
namespace AOE\Crawler\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Class TsfeHook
 * @package AOE\Crawler\Hooks
 */
class TsfeHook
{
    /**
     * Initialization hook (called after database connection)
     * Takes the "HTTP_X_T3CRAWLER" header and looks up queue record and verifies if the session comes from the system (by comparing hashes)
     *
     * @param array Parameters from frontend
     * @param object TSFE object (reference under PHP5)
     * @return void
     *
     * TODO: Write Unit test
     */
    function fe_init(&$params, $ref)
    {

        // Authenticate crawler request:
        if (isset($_SERVER['HTTP_X_T3CRAWLER'])) {
            //@todo: ask service to exclude current call for special reasons: for example no relevance because the language version is not affected

            list($queueId,$hash) = explode(':', $_SERVER['HTTP_X_T3CRAWLER']);
            list($queueRec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_crawler_queue','qid='.intval($queueId));

            // If a crawler record was found and hash was matching, set it up:
            if (is_array($queueRec) && $hash === md5($queueRec['qid'].'|'.$queueRec['set_id'].'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
                $params['pObj']->applicationData['tx_crawler']['running'] = true;
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
     * @param array  Parameters from frontend
     * @param object  TSFE object
     * @return void
     *
     * TODO: Write Unit test
     */
    function fe_feuserInit(&$params, $ref)
    {
        if ($params['pObj']->applicationData['tx_crawler']['running']) {
            $grList = $params['pObj']->applicationData['tx_crawler']['parameters']['feUserGroupList'];
            if ($grList) {
                if (!is_array($params['pObj']->fe_user->user)) $params['pObj']->fe_user->user = array();
                $params['pObj']->fe_user->user['usergroup'] = $grList;
                $params['pObj']->applicationData['tx_crawler']['log'][] = 'User Groups: '.$grList;
            }
        }
    }

    /**
     * Whether to output rendered content or not. If the crawler is running, the rendered output is never outputted!
     *
     * @param array  Parameters from frontend
     * @param object  TSFE object
     * @return void
     *
     * TODO: Write Unit test
     */
    function fe_isOutputting(&$params, $ref)
    {
        if ($params['pObj']->applicationData['tx_crawler']['running']) {
            $params['enableOutput'] = false;
        }
    }

    /**
     * Concluding: Outputting serialized information instead of letting rendered content out.
     *
     * @param array  Parameters from frontend
     * @param object  TSFE object
     * @return void
     */
    function fe_eofe(&$params, $ref)
    {
        if ($params['pObj']->applicationData['tx_crawler']['running']) {
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
                    if(is_array($params['pObj']->applicationData['tx_crawler']['content']['parameters']['procInstructions']) && in_array($pollable,$params['pObj']->applicationData['tx_crawler']['content']['parameters']['procInstructions'])){
                        if(empty($params['pObj']->applicationData['tx_crawler']['success'][$pollable])) {
                            $params['pObj']->applicationData['tx_crawler']['errorlog'][] = 'Error: Pollable extension ('.$pollable.') did not complete successfully.';
                        }
                    }
                }
            }

            // Output log data for crawler (serialized content):
            $str = serialize($params['pObj']->applicationData['tx_crawler']);
            //just make sure that no other output distracts this
            ob_clean();
            header('Content-Length: '.strlen($str));
            echo $str;
            // Exit since we don't want anymore output!
            exit;
        }
    }
}