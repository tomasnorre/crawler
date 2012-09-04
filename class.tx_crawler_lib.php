<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Crawler library, executed in a backend context
 *
 * @author Kasper Skaarhoej <kasperYYYY@typo3.com>
 */

require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_pagetree.php');
require_once(PATH_t3lib.'class.t3lib_cli.php');
require_once(PATH_t3lib.'class.t3lib_tsparser.php');

require_once t3lib_extMgm::extPath('crawler') . 'domain/events/class.tx_crawler_domain_events_dispatcher.php';
require_once t3lib_extMgm::extPath('crawler') . 'domain/reason/class.tx_crawler_domain_reason.php';

/**
 * Crawler library
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_crawler
 */
class tx_crawler_lib {

	var $setID = 0;
	var $processID ='';
	var $max_CLI_exec_time = 3600;	// One hour is max stalled time for the CLI (If the process has had the status "start" for 3600 seconds it will be regarded stalled and a new process is started.

	var $duplicateTrack = array();
	var $downloadUrls = array();

	var $incomingProcInstructions = array();
	var $incomingConfigurationSelection = array();


	var $registerQueueEntriesInternallyOnly = array();
	var $queueEntries = array();
	var $urlList = array();

	var $debugMode=FALSE;

	var $extensionSettings=array();

	var $MP = false; // mount point

	protected $processFilename;

	/**
	 * Holds the internal access mode can be 'gui','cli' or 'cli_im'
	 *
	 * @var string
	 */
	protected $accessMode;

	const CLI_STATUS_NOTHING_PROCCESSED = 0;
	const CLI_STATUS_REMAIN = 1;	//queue not empty
	const CLI_STATUS_PROCESSED = 2;	//(some) queue items where processed
	const CLI_STATUS_ABORTED = 4;	//instance didn't finish
	const CLI_STATUS_POLLABLE_PROCESSED = 8;

	/**
	 * Method to set the accessMode can be gui, cli or cli_im
	 *
	 * @return string
	 */
	public function getAccessMode() {
		return $this->accessMode;
	}

	/**
	 * @param string $accessMode
	 */
	public function setAccessMode($accessMode) {
		$this->accessMode = $accessMode;
	}



	/************************************
	 *
	 * Getting URLs based on Page TSconfig
	 *
	 ************************************/

	public function __construct() {

		$this->processFilename = PATH_site.'typo3temp/tx_crawler.proc';

		$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);
		$settings = is_array($settings) ? $settings : array();

		// read ext_em_conf_template settings and set
		$this->setExtensionSettings($settings);

		// set defaults:
		if (tx_crawler_api::convertToPositiveInteger($this->extensionSettings['countInARun']) == 0) {
			$this->extensionSettings['countInARun'] = 100;
		}
		$this->extensionSettings['processLimit'] = tx_crawler_api::forceIntegerInRange($this->extensionSettings['processLimit'],1,99,1);
	}

	/**
	 * Sets the extensions settings (unserialized pendant of $TYPO3_CONF_VARS['EXT']['extConf']['crawler']).
	 *
	 * @param array $extensionSettings
	 * @return void
	 */
	public function setExtensionSettings(array $extensionSettings) {
		$this->extensionSettings = $extensionSettings;
	}

	/**
	 * Check if the given page should be crawled
	 *
	 * @param array $pageRow
	 * @return false|string false if the page should be crawled (not excluded), true / skipMessage if it should be skipped
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 */
	public function checkIfPageShouldBeSkipped(array $pageRow) {

		$skipPage = false;
		$skipMessage = 'Skipped'; // message will be overwritten later

		// if page is hidden
		if (!$this->extensionSettings['crawlHiddenPages']) {
			if ($pageRow['hidden']) {
				$skipPage = true;
				$skipMessage = 'Because page is hidden';
			}
		}

		if (!$skipPage) {
			if (t3lib_div::inList('3,4', $pageRow['doktype']) || $pageRow['doktype']>=199)	{
				$skipPage = true;
				$skipMessage = 'Because doktype is not allowed';
			}
		}

		if (!$skipPage) {
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] as $key => $doktypeList) {
					if (t3lib_div::inList($doktypeList, $pageRow['doktype'])) {
						$skipPage = true;
						$skipMessage = 'Doktype was excluded by "'.$key.'"';
						break;
					}
				}
			}
		}

		if (!$skipPage) {
			// veto hook
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'] as $key => $func)	{
					$params = array(
						'pageRow' => $pageRow
					);
					// expects "false" if page is ok and "true" or a skipMessage if this page should _not_ be crawled
					$veto = t3lib_div::callUserFunction($func, $params, $this);
					if ($veto !== false)	{
						$skipPage = true;
						if (is_string($veto)) {
							$skipMessage = $veto;
						} else {
							$skipMessage = 'Veto from hook "'.htmlspecialchars($key).'"';
						}
						// no need to execute other hooks if a previous one return a veto
						break;
					}
				}
			}
		}

		return $skipPage ? $skipMessage : false;
	}

	/**
	 * Wrapper method for getUrlsForPageId()
	 * It returns an array of configurations and no urls!
	 *
	 * @param	array		Page record with at least doktype and uid columns.
	 * @return	array		Result (see getUrlsForPageId())
	 * @see getUrlsForPageId()
	 */
	public function getUrlsForPageRow(array $pageRow, &$skipMessage='') {

		$message = $this->checkIfPageShouldBeSkipped($pageRow);

		if ($message === false) {
			$res = $this->getUrlsForPageId($pageRow['uid']);
			$skipMessage = '';
		} else {
			$skipMessage = $message;
			$res = array();
		}

		return $res;
	}

	/**
	 * This method is used to count if there are ANY unporocessed queue entrys
	 * of a given page_id and the configuration wich matches a given hash.
	 * If there if none, we can skip an inner detail check
	 *
	 * @param int $uid
	 * @param string $configurationHash
	 * @return boolean
	 */
	protected function noUnprocessedQueueEntriesForPageWithConfigurationHashExist($uid,$configurationHash){
		/* @var $db t3lib_Db*/
		$db 				= $GLOBALS['TYPO3_DB'];
		$uid 				= intval($uid);
		$configurationHash  = $db->fullQuoteStr($configurationHash,'tx_crawler_queue');
		$res 				= $db->exec_SELECTquery('count(*) as anz','tx_crawler_queue',"page_id=".intval($uid)." AND configuration_hash=".$configurationHash." AND exec_time=0");
		$row				= $db->sql_fetch_assoc($res);

		return ($row['anz'] == 0);
	}

	/**
	 * Creates a list of URLs from input array (and submits them to queue if asked for)
	 * See Web > Info module script + "indexed_search"'s crawler hook-client using this!
	 *
	 * @param	array		Information about URLs from pageRow to crawl.
	 * @param	array		Page row
	 * @param	integer		Unix time to schedule indexing to, typically time()
	 * @param	integer		Number of requests per minute (creates the interleave between requests)
	 * @param	boolean		If set, submits the URLs to queue
	 * @param	boolean		If set (and submitcrawlUrls is false) will fill $downloadUrls with entries)
	 * @param	array		Array which is passed by reference and contains the an id per url to secure we will not crawl duplicates
	 * @param	array		Array which will be filled with URLS for download if flag is set.
	 * @param	array		Array of processing instructions
	 * @return	string		List of URLs (meant for display in backend module)
	 *
	 */
	function urlListFromUrlArray(
	array $vv,
	array $pageRow,
	$scheduledTime,
	$reqMinute,
	$submitCrawlUrls,
	$downloadCrawlUrls,
	array &$duplicateTrack,
	array &$downloadUrls,
	array $incomingProcInstructions) {

		// realurl support (thanks to Ingo Renner)
		if (t3lib_extMgm::isLoaded('realurl') && $vv['subCfg']['realurl']) {
			require_once(t3lib_extMgm::extPath('realurl') . 'class.tx_realurl.php');
			/* @var $urlObj tx_realurl */
			$urlObj = t3lib_div::makeInstance('tx_realurl');
			$urlObj->setConfig();

			if (!empty($vv['subCfg']['baseUrl'])) {
				$urlParts = parse_url($vv['subCfg']['baseUrl']);
				$host = strtolower($urlParts['host']);
				$urlObj->host = $host;

				// First pass, finding configuration OR pointer string:
				$urlObj->extConf = isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$urlObj->host]) ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$urlObj->host] : $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'];

				// If it turned out to be a string pointer, then look up the real config:
				if (is_string($urlObj->extConf)) {
					$urlObj->extConf = is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$urlObj->extConf]) ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$urlObj->extConf] : $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'];
				}

			}

			$urlObj->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'];
			if (!$GLOBALS['TSFE']->sys_page) {
				$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
			}
			if (!$GLOBALS['TSFE']->csConvObj) {
				$GLOBALS['TSFE']->csConvObj = t3lib_div::makeInstance('t3lib_cs');
			}
			if (!$GLOBALS['TSFE']->tmpl->rootLine[0]['uid']) {
				$GLOBALS['TSFE']->tmpl->rootLine[0]['uid'] = $urlObj->extConf['pagePath']['rootpage_id'];
			}
		}

		if (is_array($vv['URLs']))	{
			$configurationHash 	=	md5(serialize($vv));
			$skipInnerCheck 	=	$this->noUnprocessedQueueEntriesForPageWithConfigurationHashExist($pageRow['uid'],$configurationHash);

			foreach($vv['URLs'] as $urlQuery)	{

				if ($this->drawURLs_PIfilter($vv['subCfg']['procInstrFilter'], $incomingProcInstructions))	{

					// Calculate cHash:
					if ($vv['subCfg']['cHash'])	{
						if (version_compare(TYPO3_version, '4.7.0', '>=')) {
							/* @var $cacheHash t3lib_cacheHash */
							$cacheHash = t3lib_div::makeInstance('t3lib_cacheHash');
							$urlQuery .= '&cHash=' . $cacheHash->generateForParameters($urlQuery);
						} else {
							$pA = t3lib_div::cHashParams($urlQuery);
							if (count($pA)>1)	{
								if (t3lib_div::compat_version ('4.3')) {
									$urlQuery .= '&cHash=' . t3lib_div::calculateCHash($pA);
								} else {
									$urlQuery .= '&cHash='.rawurlencode(t3lib_div::shortMD5(serialize($pA)));
								}
							}
						}
					}

					// Create key by which to determine unique-ness:
					$uKey = $urlQuery.'|'.$vv['subCfg']['userGroups'].'|'.$vv['subCfg']['baseUrl'].'|'.$vv['subCfg']['procInstrFilter'];

					// realurl support (thanks to Ingo Renner)
					$urlQuery = 'index.php' . $urlQuery;
					if (t3lib_extMgm::isLoaded('realurl') && $vv['subCfg']['realurl']) {
						$uParts = parse_url($urlQuery);
						$urlQuery = $urlObj->encodeSpURL_doEncode($uParts['query'], $vv['subCfg']['cHash'], $urlQuery);
					}

					// Scheduled time:
					$schTime = $scheduledTime + round(count($duplicateTrack)*(60/$reqMinute));
					$schTime = floor($schTime/60)*60;

					if (isset($duplicateTrack[$uKey])) {

						//if the url key is registered just display it and do not resubmit is
						$urlList .= '<em><span class="typo3-dimmed">'.htmlspecialchars($urlQuery).'</span></em><br/>';

					} else {

						$urlList .= '['.date('d.m.y H:i', $schTime).'] '.htmlspecialchars($urlQuery);
						$this->urlList[] = '['.date('d.m.y H:i', $schTime).'] '.$urlQuery;

						$theUrl = ($vv['subCfg']['baseUrl'] ? $vv['subCfg']['baseUrl'] : t3lib_div::getIndpEnv('TYPO3_SITE_URL')) . $urlQuery;

						// Submit for crawling!
						if ($submitCrawlUrls)	{
							$added = $this->addUrl(
							$pageRow['uid'],
							$theUrl,
							$vv['subCfg'],
							$scheduledTime,
							$configurationHash,
							$skipInnerCheck
							);
							if ($added === false) {
								$urlList .= ' (Url already existed)';
							}
						} elseif ($downloadCrawlUrls)	{
							$downloadUrls[$theUrl] = $theUrl;
						}

						$urlList .= '<br />';
					}
					$duplicateTrack[$uKey] = TRUE;
				}
			}
		} else {
			$urlList = 'ERROR - no URL generated';
		}

		return $urlList;
	}

	/**
	 * Returns true if input processing instruction is amoung registered ones.
	 *
	 * @param	string		PI to test
	 * @param	array		Processing instructions
	 * @return	boolean		TRUE if found
	 */
	function drawURLs_PIfilter($piString, array $incomingProcInstructions)	{

		if (empty($incomingProcInstructions)) {
			return true;
		}

		foreach($incomingProcInstructions as $pi) {
			if (t3lib_div::inList($piString, $pi)) {
				return TRUE;
			}
		}
	}


	function getPageTSconfigForId($id) {
		if(!$this->MP){
			$pageTSconfig = t3lib_BEfunc::getPagesTSconfig($id);
		} else {
			list(,$mountPointId) = explode('-', $this->MP);
			$pageTSconfig = t3lib_BEfunc::getPagesTSconfig($mountPointId);
		}

		// Call a hook to alter configuration
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'])) {
			$params = array(
				'pageId' => $id,
				'pageTSConfig' => &$pageTSconfig
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'] as $userFunc) {
				t3lib_div::callUserFunction($userFunc, $params, $this);
			}
		}

		return $pageTSconfig;
	}

	/**
	 * This methods returns an array of configurations.
	 * And no urls!
	 *
	 * @param	integer		Page ID
	 * @return	array		configurations from pagets and configuration records
	 */
	protected function getUrlsForPageId($id)	{

		/**
		 * Get configuration from tsConfig
		 */

		// Get page TSconfig for page ID:
		$pageTSconfig = $this->getPageTSconfigForId($id);

		$res = array();

		if (is_array($pageTSconfig) && is_array($pageTSconfig['tx_crawler.']['crawlerCfg.']))	{
			$crawlerCfg = $pageTSconfig['tx_crawler.']['crawlerCfg.'];

			if (is_array($crawlerCfg['paramSets.']))	{
				foreach($crawlerCfg['paramSets.'] as $key => $values)	{
					if (!is_array($values))	{

						// Sub configuration for a single configuration string:
						$subCfg = (array)$crawlerCfg['paramSets.'][$key.'.'];
						$subCfg['key'] = $key;

						if (strcmp($subCfg['procInstrFilter'],''))	{
							$subCfg['procInstrFilter'] = implode(',',t3lib_div::trimExplode(',',$subCfg['procInstrFilter']));
						}
						$pidOnlyList = implode(',',t3lib_div::trimExplode(',',$subCfg['pidsOnly'],1));

							// process configuration if it is not page-specific or if the specific page is the current page:
						if (!strcmp($subCfg['pidsOnly'],'') || t3lib_div::inList($pidOnlyList,$id))	{

								// add trailing slash if not present
							if (!empty($subCfg['baseUrl']) && substr($subCfg['baseUrl'], -1) != '/') {
								$subCfg['baseUrl'] .= '/';
							}

								// Explode, process etc.:
							$res[$key] = array();
							$res[$key]['subCfg'] = $subCfg;
							$res[$key]['paramParsed'] = $this->parseParams($values);
							$res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'],$id);
							$res[$key]['origin'] = 'pagets';

								// recognize MP value
							if(!$this->MP){
								$res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'],array('?id='.$id));
							} else {
								$res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'],array('?id='.$id.'&MP='.$this->MP));
							}
						}
					}
				}

			}
		}

		/**
		 * Get configutation from tx_crawler_configuration records
		 */

			// get records along the rootline
		$rootLine = t3lib_BEfunc::BEgetRootLine($id);

		foreach ($rootLine as $page) {
			$configurationRecordsForCurrentPage = t3lib_BEfunc::getRecordsByField(
				'tx_crawler_configuration',
				'pid',
				intval($page['uid']),
				t3lib_BEfunc::BEenableFields('tx_crawler_configuration') . t3lib_BEfunc::deleteClause('tx_crawler_configuration')
			);

			if (is_array($configurationRecordsForCurrentPage)) {
				foreach ($configurationRecordsForCurrentPage as $configurationRecord) {

						// check access to the configuration record
					if (empty($configurationRecord['begroups']) || $GLOBALS['BE_USER']->isAdmin() || $this->hasGroupAccess($GLOBALS['BE_USER']->user['usergroup_cached_list'], $configurationRecord['begroups'])) {

						$pidOnlyList = implode(',',t3lib_div::trimExplode(',',$configurationRecord['pidsonly'],1));

							// process configuration if it is not page-specific or if the specific page is the current page:
						if (!strcmp($configurationRecord['pidsonly'],'') || t3lib_div::inList($pidOnlyList,$id)) {
							$key = $configurationRecord['name'];

								// don't overwrite previously defined paramSets
							if (!isset($res[$key])) {

									/* @var $TSparserObject t3lib_tsparser */
								$TSparserObject = t3lib_div::makeInstance('t3lib_tsparser');
								$TSparserObject->parse($configurationRecord['processing_instruction_parameters_ts']);

								$subCfg = array(
									'procInstrFilter' => $configurationRecord['processing_instruction_filter'],
									'procInstrParams.' => $TSparserObject->setup,
									'baseUrl' => $this->getBaseUrlForConfigurationRecord($configurationRecord['base_url'], $configurationRecord['sys_domain_base_url']),
									'realurl' => $configurationRecord['realurl'],
									'cHash' => $configurationRecord['chash'],
									'userGroups' => $configurationRecord['fegroups'],
									'exclude' => $configurationRecord['exclude'],
									'workspace' => $configurationRecord['sys_workspace_uid'],
									'key' => $key,
								);

									// add trailing slash if not present
								if (!empty($subCfg['baseUrl']) && substr($subCfg['baseUrl'], -1) != '/') {
									$subCfg['baseUrl'] .= '/';
								}
								if (!in_array($id, $this->expandExcludeString($subCfg['exclude']))) {
									$res[$key] = array();
									$res[$key]['subCfg'] = $subCfg;
									$res[$key]['paramParsed'] = $this->parseParams($configurationRecord['configuration']);
									$res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $id);
									$res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], array('?id='.$id. ((abs($subCfg['workspace'])>0)?'&ADMCMD_view=1&ADMCMD_editIcons=0&ADMCMD_previewWS='.$subCfg['workspace']:'')));
									$res[$key]['origin'] = 'tx_crawler_configuration_'.$configurationRecord['uid'];
								}
							}
						}
					}
				}
			}
		}

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['processUrls']))	{
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['processUrls'] as $func)	{
				$params = array(
					'res' => &$res,
				);
				t3lib_div::callUserFunction($func, $params, $this);
			}
		}

		return $res;
	}

	/**
	 * checks if a domain record exist and returns a baseurl based on the record. If not the given baseUrl string is used.
	 * @param string $baseUrl
	 * @param integer $sysDomainUid
	 */
	protected function getBaseUrlForConfigurationRecord($baseUrl,$sysDomainUid) {
		$sysDomainUid = intval($sysDomainUid);
		if ($sysDomainUid > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'sys_domain',
				'uid = '.$sysDomainUid .
				t3lib_BEfunc::BEenableFields('sys_domain') .
				t3lib_BEfunc::deleteClause('sys_domain')
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if ($row['domainName'] != '') {
				return 'http://'.$row['domainName'];
			}
		}
		return $baseUrl;
	}

	function getConfigurationsForBranch($rootid, $depth) {

		$configurationsForBranch = array();

		$pageTSconfig = $this->getPageTSconfigForId($rootid);
		if (is_array($pageTSconfig) && is_array($pageTSconfig['tx_crawler.']['crawlerCfg.']) && is_array($pageTSconfig['tx_crawler.']['crawlerCfg.']['paramSets.']))	{

			$sets = $pageTSconfig['tx_crawler.']['crawlerCfg.']['paramSets.'];
			if(is_array($sets)) {
				foreach($sets as $key=>$value) {
					if(!is_array($value)) continue;
					$configurationsForBranch[] = substr($key,-1)=='.'?substr($key,0,-1):$key;
				}

			}
		}
		$pids = array();
		$rootLine = t3lib_BEfunc::BEgetRootLine($rootid);
		foreach($rootLine as $node) {
			$pids[] = $node['uid'];
		}
		/* @var t3lib_pageTree */
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$tree->init('AND ' . $perms_clause);
		$tree->getTree($rootid, $depth, '');
		foreach($tree->tree as $node) {
			$pids[] = $node['row']['uid'];
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_crawler_configuration',
			'pid IN ('.implode(',', $pids).') '.
			t3lib_BEfunc::BEenableFields('tx_crawler_configuration') .
			t3lib_BEfunc::deleteClause('tx_crawler_configuration').' '.
			t3lib_BEfunc::versioningPlaceholderClause('tx_crawler_configuration').' '
		);
		$rows = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$configurationsForBranch[] = $row['name'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $configurationsForBranch;
	}

	/**
	 * Check if a user has access to an item
	 * (e.g. get the group list of the current logged in user from $GLOBALS['TSFE']->gr_list)
	 *
	 * @see	t3lib_pageSelect::getMultipleGroupsWhereClause()
	 * @param string comma-separated list of (fe_)group uids from a user
	 * @param string comma-separated list of (fe_)group uids of the item to access
	 * @return bool true if at least one of the users group uids is in the access list or the access list is empty
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 * @since 2009-01-19
	 */
	function hasGroupAccess($groupList, $accessList) {
		if (empty($accessList)) {
			return true;
		}
		foreach(t3lib_div::intExplode(',', $groupList) as $groupUid) {
			if (t3lib_div::inList($accessList, $groupUid)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Parse GET vars of input Query into array with key=>value pairs
	 *
	 * @param	string		Input query string
	 * @return	array		Keys are Get var names, values are the values of the GET vars.
	 */
	function parseParams($inputQuery)	{

			// Extract all GET parameters into an ARRAY:
		$paramKeyValues = array();
		$GETparams = explode('&', $inputQuery);
		foreach($GETparams as $paramAndValue)	{
			list($p,$v) = explode('=', $paramAndValue, 2);
			if (strlen($p))		{
				$paramKeyValues[rawurldecode($p)] = rawurldecode($v);
			}
		}

		return $paramKeyValues;
	}

	/**
	 * Will expand the parameters configuration to individual values. This follows a certain syntax of the value of each parameter.
	 * Syntax of values:
	 * - Basically: If the value is wrapped in [...] it will be expanded according to the following syntax, otherwise the value is taken literally
	 * - Configuration is splitted by "|" and the parts are processed individually and finally added together
	 * - For each configuration part:
	 * 		- "[int]-[int]" = Integer range, will be expanded to all values in between, values included, starting from low to high (max. 1000). Example "1-34" or "-40--30"
	 * 		- "_TABLE:[TCA table name];[_PID:[optional page id, default is current page]];[_ENABLELANG:1]" = Look up of table records from PID, filtering out deleted records. Example "_TABLE:tt_content; _PID:123"
	 *  	  _ENABLELANG:1 picks only original records without their language overlays
	 * 		- Default: Literal value
	 *
	 * @param	array		Array with key (GET var name) and values (value of GET var which is configuration for expansion)
	 * @param	integer		Current page ID
	 * @return	array		Array with key (GET var name) with the value being an array of all possible values for that key.
	 */
	function expandParameters($paramArray, $pid)	{
		global $TCA;

			// Traverse parameter names:
		foreach($paramArray as $p => $v)	{
			$v = trim($v);

				// If value is encapsulated in square brackets it means there are some ranges of values to find, otherwise the value is literal
			if (substr($v,0,1)==='[' && substr($v,-1)===']')	{
					// So, find the value inside brackets and reset the paramArray value as an array.
				$v = substr($v,1,-1);
				$paramArray[$p] = array();

					// Explode parts and traverse them:
				$parts = explode('|',$v);
				foreach($parts as $pV)	{

						// Look for integer range: (fx. 1-34 or -40--30 // reads minus 40 to minus 30)
					if (preg_match('/^(-?[0-9]+)\s*-\s*(-?[0-9]+)$/',trim($pV),$reg))	{	// Integer range:

							// Swap if first is larger than last:
						if ($reg[1] > $reg[2])	{
							$temp = $reg[2];
							$reg[2] = $reg[1];
							$reg[1] = $temp;
						}

							// Traverse range, add values:
						$runAwayBrake = 1000;	// Limit to size of range!
						for($a=$reg[1]; $a<=$reg[2];$a++)	{
							$paramArray[$p][] = $a;
							$runAwayBrake--;
							if ($runAwayBrake<=0)	{
								break;
							}
						}
					} elseif (substr(trim($pV),0,7)=='_TABLE:')	{

							// Parse parameters:
						$subparts = t3lib_div::trimExplode(';',$pV);
						$subpartParams = array();
						foreach($subparts as $spV)	{
							list($pKey,$pVal) = t3lib_div::trimExplode(':',$spV);
							$subpartParams[$pKey] = $pVal;
						}

							// Table exists:
						if (isset($TCA[$subpartParams['_TABLE']]))	{
							t3lib_div::loadTCA($subpartParams['_TABLE']);
							$lookUpPid = isset($subpartParams['_PID']) ? intval($subpartParams['_PID']) : $pid;
							$pidField = isset($subpartParams['_PIDFIELD']) ? trim($subpartParams['_PIDFIELD']) : 'pid';
							$where = isset($subpartParams['_WHERE']) ? $subpartParams['_WHERE'] : '';
							$addTable = isset($subpartParams['_ADDTABLE']) ? $subpartParams['_ADDTABLE'] : '';

							$fieldName = $subpartParams['_FIELD'] ? $subpartParams['_FIELD'] : 'uid';
							if ($fieldName==='uid' || $TCA[$subpartParams['_TABLE']]['columns'][$fieldName]) {

								$andWhereLanguage = '';
								$transOrigPointerField = $TCA[$subpartParams['_TABLE']]['ctrl']['transOrigPointerField'];

								if ($subpartParams['_ENABLELANG'] && $transOrigPointerField) {
									$andWhereLanguage = ' AND ' . $GLOBALS['TYPO3_DB']->quoteStr($transOrigPointerField, $subpartParams['_TABLE']) .' <= 0 ';
								}

								$where = $GLOBALS['TYPO3_DB']->quoteStr($pidField, $subpartParams['_TABLE']) .'='.intval($lookUpPid) . ' ' .
									$andWhereLanguage . $where;

								$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
									$fieldName,
									$subpartParams['_TABLE'] . $addTable,
									$where . t3lib_BEfunc::deleteClause($subpartParams['_TABLE']),
									'',
									'',
									'',
									$fieldName
								);

								if (is_array($rows))	{
									$paramArray[$p] = array_merge($paramArray[$p],array_keys($rows));
								}
							}
						}
					} else {	// Just add value:
						$paramArray[$p][] = $pV;
					}
						// Hook for processing own expandParameters place holder
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'])) {
						$_params = array(
							'pObj' => &$this,
							'paramArray' => &$paramArray,
							'currentKey' => $p,
							'currentValue' => $pV,
							'pid' => $pid
						);
						foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'] as $key => $_funcRef)	{
							t3lib_div::callUserFunction($_funcRef, $_params, $this);
						}
					}
				}

					// Make unique set of values and sort array by key:
				$paramArray[$p] = array_unique($paramArray[$p]);
				ksort($paramArray);
			} else {
					// Set the literal value as only value in array:
				$paramArray[$p] = array($v);
			}
		}

		return $paramArray;
	}

	/**
	 * Compiling URLs from parameter array (output of expandParameters())
	 * The number of URLs will be the multiplication of the number of parameter values for each key
	 *
	 * @param	array		output of expandParameters(): Array with keys (GET var names) and for each an array of values
	 * @param	array		URLs accumulated in this array (for recursion)
	 * @return	array		URLs accumulated, if number of urls exceed 'maxCompileUrls' it will return false as an error!
	 */
	function compileUrls($paramArray, $urls=array())	{

		if (count($paramArray) && is_array($urls))	{

				// shift first off stack:
			reset($paramArray);
			$varName = key($paramArray);
			$valueSet = array_shift($paramArray);

				// Traverse value set:
			$newUrls = array();
			foreach($urls as $url)	{
				foreach($valueSet as $val)	{
					$newUrls[] = $url.(strcmp($val,'') ? '&'.rawurlencode($varName).'='.rawurlencode($val) : '');

					if (count($newUrls) >  tx_crawler_api::forceIntegerInRange($this->extensionSettings['maxCompileUrls'], 1, 1000000000, 10000))	{
						break;
						break;
					}
				}
			}
			$urls = $newUrls;
			$urls = $this->compileUrls($paramArray, $urls);
		}

		return $urls;
	}











	/************************************
	 *
	 * Crawler log
	 *
	 ************************************/

	/**
	 * Return array of records from crawler queue for input page ID
	 *
	 * @param	integer		Page ID for which to look up log entries.
	 * @param	string		Filter: "all" => all entries, "pending" => all that is not yet run, "finished" => all complete ones
	 * @param	boolean		If TRUE, then entries selected at DELETED(!) instead of selected!
	 * @param	integer		Limit the amount of entires per page default is 10
	 * @return	array
	 */
	function getLogEntriesForPageId($id,$filter='',$doFlush=FALSE, $doFullFlush=FALSE, $itemsPerPage=10)	{

		switch($filter)	{
			case 'pending':
				$addWhere = ' AND exec_time=0';
				break;
			case 'finished':
				$addWhere = ' AND exec_time>0';
				break;
			default:
				$addWhere = '';
				break;
		}

		if ($doFlush)	{
			$this->flushQueue( ($doFullFlush?'1=1':('page_id='.intval($id))) .$addWhere);
			return array();
		} else {
			return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
				'tx_crawler_queue',
				'page_id=' . intval($id) . $addWhere, '', 'scheduled DESC',
				(intval($itemsPerPage)>0 ? intval($itemsPerPage) : ''));
		}
	}

	/**
	 * Return array of records from crawler queue for input set ID
	 *
	 * @param	integer		Set ID for which to look up log entries.
	 * @param	string		Filter: "all" => all entries, "pending" => all that is not yet run, "finished" => all complete ones
	 * @param	boolean		If TRUE, then entries selected at DELETED(!) instead of selected!
	 * @param	integer		Limit the amount of entires per page default is 10
	 * @return	array
	 */
	function getLogEntriesForSetId($set_id,$filter='',$doFlush=FALSE, $doFullFlush=FALSE, $itemsPerPage=10)	{

		switch($filter)	{
			case 'pending':
				$addWhere = ' AND exec_time=0';
				break;
			case 'finished':
				$addWhere = ' AND exec_time>0';
				break;
			default:
				$addWhere = '';
				break;
		}

		if ($doFlush)	{
			$this->flushQueue($doFullFlush?'':('set_id='.intval($set_id).$addWhere));
			return array();
		} else {
			return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
				'tx_crawler_queue',
				'set_id='.intval($set_id).$addWhere,'','scheduled DESC',
				(intval($itemsPerPage)>0 ? intval($itemsPerPage) : ''));
		}
	}

	/**
	 * Removes queue entires
	 *
	 * @param $where	SQL related filter for the entries which should be removed
	 * @return void
	 */
	protected function flushQueue($where='') {

		$realWhere = strlen($where)>0?$where:'1=1';

		if(tx_crawler_domain_events_dispatcher::getInstance()->hasObserver('queueEntryFlush')) {
			$groups = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('DISTINCT set_id','tx_crawler_queue',$realWhere);
			foreach($groups as $group) {
				tx_crawler_domain_events_dispatcher::getInstance()->post('queueEntryFlush',$group['set_id'], $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, set_id','tx_crawler_queue',$realWhere.' AND set_id="'.$group['set_id'].'"'));
			}
		}

		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_crawler_queue', $realWhere);
	}

	/**
	 * Adding call back entries to log (called from hooks typically, see indexed search class "class.crawler.php"
	 *
	 * @param	integer		Set ID
	 * @param	array		Parameters to pass to call back function
	 * @param	string		Call back object reference, eg. 'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_crawler'
	 * @param	integer		Page ID to attach it to
	 * @param	integer		Time at which to activate
	 * @return	void
	 */
	function addQueueEntry_callBack($setId,$params,$callBack,$page_id=0,$schedule=0)	{

		if (!is_array($params))	$params = array();
		$params['_CALLBACKOBJ'] = $callBack;

			// Compile value array:
		$fieldArray = array(
			'page_id' => intval($page_id),
			'parameters' => serialize($params),
			'scheduled' => intval($schedule) ? intval($schedule) : $this->getCurrentTime(),
			'exec_time' => 0,
			'set_id' => intval($setId),
			'result_data' => '',
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_crawler_queue',$fieldArray);
	}











	/************************************
	 *
	 * URL setting
	 *
	 ************************************/

	/**
	 * Setting a URL for crawling:
	 *
	 * @param	integer		Page ID
	 * @param	string		Complete URL
	 * @param	array		Sub configuration array (from TS config)
	 * @param	integer		Scheduled-time
	 * @param 	string		(optional) configuration hash
	 * @param 	bool		(optional) skip inner duplication check
	 * @return	bool		true if the url was added, false if it already existed
	 */
	function addUrl (
		$id,
		$url,
		array $subCfg,
		$tstamp,
		$configurationHash='',
		$skipInnerDuplicationCheck=false
	) {

		$urlAdded = false;

			// Creating parameters:
		$parameters = array(
			'url' => $url
		);

			// fe user group simulation:
		$uGs = implode(',',array_unique(t3lib_div::intExplode(',',$subCfg['userGroups'],1)));
		if ($uGs)	{
			$parameters['feUserGroupList'] = $uGs;
		}

			// Setting processing instructions
		$parameters['procInstructions'] = t3lib_div::trimExplode(',',$subCfg['procInstrFilter']);
		if (is_array($subCfg['procInstrParams.']))	{
			$parameters['procInstrParams'] = $subCfg['procInstrParams.'];
		}


			// Compile value array:
		$parameters_serialized = serialize($parameters);
		$fieldArray = array(
			'page_id' => intval($id),
			'parameters' => $parameters_serialized,
			'parameters_hash' => t3lib_div::shortMD5($parameters_serialized),
			'configuration_hash' => $configurationHash,
			'scheduled' => $tstamp,
			'exec_time' => 0,
			'set_id' => intval($this->setID),
			'result_data' => '',
			'configuration' => $subCfg['key'],
		);

		if ($this->registerQueueEntriesInternallyOnly)	{
				//the entries will only be registered and not stored to the database
			$this->queueEntries[] = $fieldArray;
		} else {

			if(!$skipInnerDuplicationCheck){
					// check if there is already an equal entry
				$rows = $this->getDuplicateRowsIfExist($tstamp,$fieldArray);
			}

			if (count($rows) == 0) {
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_crawler_queue', $fieldArray);
				$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();
				$rows[] = $uid;
				$urlAdded = true;
				tx_crawler_domain_events_dispatcher::getInstance()->post('urlAddedToQueue',$this->setID,array('uid' => $uid, 'fieldArray' => $fieldArray));
			}else{
				tx_crawler_domain_events_dispatcher::getInstance()->post('duplicateUrlInQueue',$this->setID,array('rows' => $rows, 'fieldArray' => $fieldArray));
			}
		}

		return $urlAdded;
	}

	/**
	 * This method determines duplicates for a queue entry with the same parameters and this timestamp.
	 * If the timestamp is in the past, it will check if there is any unprocessed queue entry in the past.
	 * If the timestamp is in the future it will check, if the queued entry has exactly the same timestamp
	 *
	 * @param int $tstamp
	 * @param string $parameters
	 * @author Fabrizio Branca
	 * @author Timo Schmidt
	 * @return array;
	 */
	protected function getDuplicateRowsIfExist($tstamp,$fieldArray){
		$rows = array();

		$currentTime = $this->getCurrentTime();

			//if this entry is scheduled with "now"
		if ($tstamp <= $currentTime) {
			if($this->extensionSettings['enableTimeslot']){
				$timeBegin 	= $currentTime - 100;
				$timeEnd 	= $currentTime + 100;
				$where 		= ' ((scheduled BETWEEN '.$timeBegin.' AND '.$timeEnd.' ) OR scheduled <= '. $currentTime.') ';
			}else{
				$where = 'scheduled <= ' . $currentTime;
			}
		} elseif ($tstamp > $currentTime) {
				//entry with a timestamp in the future need to have the same schedule time
			$where = 'scheduled = ' . $tstamp ;
		}

		if(!empty($where)){
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'qid',
				'tx_crawler_queue',
				$where.
				' AND NOT exec_time' .
				' AND NOT process_id '.
				' AND page_id='.intval($fieldArray['page_id']).
				' AND parameters_hash = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($fieldArray['parameters_hash'], 'tx_crawler_queue')
			);

			if (is_array($result)) {
				foreach ($result as $value) {
					$rows[] = $value['qid'];
				}
			}
		}


		return $rows;
	}

	/**
	 * Returns the current system time
	 *
	 * @author Timo Schmidt <schmidt@aoemedia.de>
	 * @return int
	 */
	public function getCurrentTime(){
		return time();
	}



	/************************************
	 *
	 * URL reading
	 *
	 ************************************/

	/**
	 * Read URL for single queue entry
	 *
	 * @param	integer		Queue entry id
	 * @param	boolean		If set, will process even if exec_time has been set!
	 * @return	void
	 */
	function readUrl($queueId, $force=FALSE)	{
		$ret = 0;
		if ($this->debugMode) t3lib_div::devlog('crawler-readurl start '.microtime(true),__FUNCTION__);
			// Get entry:
		list($queueRec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_crawler_queue','qid='.intval($queueId).($force ? '' : ' AND exec_time=0 AND process_scheduled > 0'));

		if (is_array($queueRec))	{
				// Set exec_time to lock record:
			$field_array = array('exec_time' => $this->getCurrentTime());

			if(isset($this->processID)){
					//if mulitprocessing is used we need to store the id of the process which has handled this entry
				$field_array['process_id_completed'] = $this->processID;
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue','qid='.intval($queueId), $field_array);

			$result = $this->readUrl_exec($queueRec);
			$resultData = unserialize($result['content']);

				//atm there's no need to point to specific pollable extensions
			if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'] as $pollable) {
					// only check the success value if the instruction is runnig
					// it is important to name the pollSuccess key same as the procInstructions key
					if(is_array($resultData['parameters']['procInstructions']) && in_array($pollable,$resultData['parameters']['procInstructions'])){
						if(!empty($resultData['success'][$pollable]) && $resultData['success'][$pollable]) {
							$ret |= self::CLI_STATUS_POLLABLE_PROCESSED;
						}
					}
				}
			}

				// Set result in log which also denotes the end of the processing of this entry.
			$field_array = array('result_data' => serialize($result));
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue','qid='.intval($queueId), $field_array);
		}
		if ($this->debugMode) t3lib_div::devlog('crawler-readurl stop '.microtime(true),__FUNCTION__);
		return $ret;
	}

	/**
	 * Read URL for not-yet-inserted log-entry
	 *
	 * @param	integer		Queue field array,
	 * @return	void
	 */
	function readUrlFromArray($field_array)	{

			// Set exec_time to lock record:
		$field_array['exec_time'] = $this->getCurrentTime();
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_crawler_queue', $field_array);
		$queueId = $field_array['qid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();

		$result = $this->readUrl_exec($field_array);

			// Set result in log which also denotes the end of the processing of this entry.
		$field_array = array('result_data' => serialize($result));
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue','qid='.intval($queueId), $field_array);

		return $result;
	}

	/**
	 * Read URL for a queue record
	 *
	 * @param	array		Queue record
	 * @return	string		Result output.
	 */
	function readUrl_exec($queueRec)	{
			// Decode parameters:
		$parameters = unserialize($queueRec['parameters']);
		$result = 'ERROR';
		if (is_array($parameters))	{
			if ($parameters['_CALLBACKOBJ'])	{	// Calling object:
				$objRef = $parameters['_CALLBACKOBJ'];
				$callBackObj = &t3lib_div::getUserObj($objRef);
				if (is_object($callBackObj))	{
					unset($parameters['_CALLBACKOBJ']);
					$result = array('content' => serialize($callBackObj->crawler_execute($parameters,$this)));
				} else {
					$result = array('content' => 'No object: '.$objRef);
				}
			} else {	// Regular FE request:

					// Prepare:
				$crawlerId = $queueRec['qid'].':'.md5($queueRec['qid'].'|'.$queueRec['set_id'].'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);

					// Get result:
				$result = $this->requestUrl($parameters['url'],$crawlerId);

				tx_crawler_domain_events_dispatcher::getInstance()->post('urlCrawled',$queueRec['set_id'],array('url' => $parameters['url'], 'result' => $result));
			}
		}


		return $result;
	}

	/**
	 * Read the input URL by fsocket
	 *
	 * @param	string		URL to read
	 * @param	string		Crawler ID string (qid + hash to verify)
	 * @param	integer		Timeout time
	 * @param	integer		Recursion limiter for 302 redirects
	 * @return	array		Array with content
	 */
	function requestUrl($originalUrl, $crawlerId, $timeout=2, $recursion=10)	{

		if(!$recursion) return false;
			// Parse URL, checking for scheme:
		$url = parse_url($originalUrl);

		if($url === FALSE) {
			if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Could not parse_url() for string "%s"', $url), 'crawler', 4, array('crawlerId' => $crawlerId));
			return FALSE;
		}

		if(!in_array($url['scheme'],array('','http','https'))) {
			if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Scheme does not match for url "%s"', $url), 'crawler', 4, array('crawlerId' => $crawlerId));
			return FALSE;
		}

		$reqHeaders = $this->buildRequestHeaderArray($url, $crawlerId);


			// direct request
		if ($this->extensionSettings['makeDirectRequests']) {
			$cmd = escapeshellcmd($this->extensionSettings['phpPath']);
			$cmd .= ' ';
			$cmd .= escapeshellarg(t3lib_extMgm::extPath('crawler').'cli/bootstrap.php');
			$cmd .= ' ';
			$cmd .= escapeshellarg($this->getFrontendBasePath());
			$cmd .= ' ';
			$cmd .= escapeshellarg($originalUrl);
			$cmd .= ' ';
			$cmd .= escapeshellarg(base64_encode(serialize($reqHeaders)));

			$startTime = microtime(true);
			$content = $this->executeShellCommand($cmd);
			$time = microtime(true) - $startTime;

			$this->log($originalUrl . $time);

			$result = array(
				'request' => implode("\r\n",$reqHeaders)."\r\n\r\n",
				'headers' => '',
				'content' => $content
			);

			return $result;
		}


			// thanks to Pierrick Caillon for adding proxy support
		$rurl = $url;
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] && $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']) {
			$rurl = parse_url($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);
			$url['path'] = $url['scheme'] . '://' . $url['host'] . ($url['port'] > 0 ? ':' . $url['port'] : '') . $url['path'];
			$reqHeaders = $this->buildRequestHeaderArray($url, $crawlerId);
		}

		$host = $rurl['host'];
		if ($url['scheme'] == 'https') {
			$host = 'ssl://' . $host;
			$port = ($rurl['port'] > 0) ? $rurl['port'] : 443;
		} else {
			$port = ($rurl['port'] > 0) ? $rurl['port'] : 80;
		}

		$startTime = microtime(true);
		$fp = fsockopen($host, $port, $errno, $errstr, $timeout);

		if (!$fp)	{
			if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Error while opening "%s"', $url), 'crawler', 4, array('crawlerId' => $crawlerId));
			return FALSE;
		} else {	// Requesting...:

				// Request message:
			$msg = implode("\r\n",$reqHeaders)."\r\n\r\n";

			fputs ($fp, $msg);
				// Read response:
			$d = array();
			$part = 'headers';

			$isFirstLine=TRUE;
			$contentLength=-1;
			$blocksize=2048;
			while (!feof($fp)) {
				$line = fgets ($fp,$blocksize);
				if (($part==='headers' && trim($line)==='') && !$isFirstLine)	{
						// switch to "content" part if empty row detected - this should not be the first row of the response anyway
					$part = 'content';
				} elseif(($part==='headers') && stristr($line,'Content-Length: ')) {
					$contentLength = intval(str_ireplace('Content-Length: ','',$line));
					if ($this->debugMode) t3lib_div::devlog('crawler - Content-Length detected: '.$contentLength,__FUNCTION__);
					$d[$part][] = $line;
				} else {
					$d[$part][] = $line;

					if(($contentLength != -1) && ($contentLength <= strlen(implode('',(array)$d['content'])))) {
						if ($this->debugMode) t3lib_div::devlog('crawler -stop reading URL- Content-Length reached',__FUNCTION__);
						break;
					}

				}
				$isFirstLine=FALSE;
			}
			fclose ($fp);

			$time = microtime(true) - $startTime;

			$this->log($originalUrl .' '.$time);

				// Implode content and headers:
			$result = array(
				'request' => $msg,
				'headers' => implode('', $d['headers']),
				'content' => implode('', (array)$d['content'])
			);

			if(($this->extensionSettings['follow30x']) && ($newUrl = $this->getRequestUrlFrom302Header($d['headers'],$url['user'],$url['pass']))) {
				$result = array_merge(array('parentRequest'=>$result), $this->requestUrl($newUrl, $crawlerId, $recursion--));

				$newRequestUrl = $this->requestUrl($newUrl, $crawlerId, $timeout, --$recursion);
				if (is_array($newRequestUrl)) {
					$result = array_merge(array('parentRequest'=>$result), $newRequestUrl);
				} else {
					if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Error while opening "%s"', $url), 'crawler', 4, array('crawlerId' => $crawlerId));
					return FALSE;
				}
			}
			return $result;
		}
	}

	/**
	 * Gets the base path of the website frontend.
	 * (e.g. if you call http://mydomain.com/cms/index.php in
	 * the browser the base path is "/cms/")
	 *
	 * @return string Base path of the website frontend
	 */
	protected function getFrontendBasePath() {
		$frontendBasePath = '/';

		// Get the path from the extension settings:
		if (isset($this->extensionSettings['frontendBasePath']) && $this->extensionSettings['frontendBasePath']) {
			$frontendBasePath = $this->extensionSettings['frontendBasePath'];
		// If not in CLI mode the base path can be determined from $_SERVER environment:
		} elseif (!defined('TYPO3_cliMode') || !TYPO3_cliMode) {
			t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
		}

		// Base path must be '/<pathSegements>/':
		if ($frontendBasePath != '/') {
			$frontendBasePath = '/' . ltrim($frontendBasePath, '/');
			$frontendBasePath = rtrim($frontendBasePath, '/') . '/';
		}

		return $frontendBasePath;
	}

	/**
	 * Executes a shell command and returns the outputted result.
	 *
	 * @param string $command Shell command to be executed
	 * @return string Outputted result of the command execution
	 */
	protected function executeShellCommand($command) {
		$result = shell_exec($command);
		return $result;
	}

	/**
	 * @param message
	 */
	protected function log($message) {
		if (!empty($this->extensionSettings['logFileName'])) {
			@file_put_contents($this->extensionSettings['logFileName'], date('Ymd His') . $message . "\n", FILE_APPEND);
		}
	}

	/**
	 * Builds HTTP request headers.
	 *
	 * @param $url
	 * @param $crawlerId
	 * @return array
	 */
	function buildRequestHeaderArray(array $url, $crawlerId) {
		$reqHeaders = array();
		$reqHeaders[] = 'GET '.$url['path'].($url['query'] ? '?'.$url['query'] : '').' HTTP/1.0';
		$reqHeaders[] = 'Host: '.$url['host'];
		if (stristr($url['query'],'ADMCMD_previewWS')) {
			$reqHeaders[] = 'Cookie: $Version="1"; be_typo_user="1"; $Path=/';
		}
		$reqHeaders[] = 'Connection: close';
		if ($url['user']!='') {
			$reqHeaders[] = 'Authorization: Basic '. base64_encode($url['user'].':'.$url['pass']);
		}
		$reqHeaders[] = 'X-T3crawler: '.$crawlerId;
		$reqHeaders[] = 'User-Agent: TYPO3 crawler';
		return $reqHeaders;
	}

	/**
	 * Check if the submitted HTTP-Header contains a redirect location and built new crawler-url
	 *
	 * @param	array		HTTP Header
	 * @param	string		HTTP Auth. User
	 * @param	string		HTTP Auth. Password
	 * @return	string		URL from redirection
	 */
	function getRequestUrlFrom302Header($headers,$user='',$pass='') {
		if(!is_array($headers)) return false;
		if(!(stristr($headers[0],'301 Moved') || stristr($headers[0],'302 Found') || stristr($headers[0],'302 Moved'))) return false;

		foreach($headers as $hl) {
			$tmp = explode(": ",$hl);
			$header[trim($tmp[0])] = trim($tmp[1]);
			if(trim($tmp[0])=='Location') break;
		}
		if(!array_key_exists('Location',$header)) return false;

		if($user!='') {
			if(!($tmp = parse_url($header['Location']))) return false;
			$newUrl = $tmp['scheme'] . '://' . $user . ':' . $pass . '@' . $tmp['host'] . $tmp['path'];
			if($tmp['query']!='') $newUrl .= '?' . $tmp['query'];
		} else {
			$newUrl = $header['Location'];
		}
		return $newUrl;
	}








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



	/*****************************
	 *
	 * Compiling URLs to crawl - tools
	 *
	 *****************************/

	/**
	 * @param	integer		Root page id to start from.
	 * @param	integer		Depth of tree, 0=only id-page, 1= on sublevel, 99 = infinite
	 * @param	integer		Unix Time when the URL is timed to be visited when put in queue
	 * @param	integer		Number of requests per minute (creates the interleave between requests)
	 * @param	boolean		If set, submits the URLs to queue in database (real crawling)
	 * @param	boolean		If set (and submitcrawlUrls is false) will fill $downloadUrls with entries)
	 * @param	array		Array of processing instructions
	 * @param	array		Array of configuration keys
	 * @return	string		HTML code
	 */
	function getPageTreeAndUrls(
		$id,
		$depth,
		$scheduledTime,
		$reqMinute,
		$submitCrawlUrls,
		$downloadCrawlUrls,
		array $incomingProcInstructions,
		array $configurationSelection
	) {

		global $BACK_PATH;
		global $LANG;
		if (!is_object($LANG)) {
			require_once(t3lib_extMgm::extPath('lang', 'lang.php'));
			$LANG = t3lib_div::makeInstance('language');
			$LANG->init(0);
		}
		$this->scheduledTime = $scheduledTime;
		$this->reqMinute = $reqMinute;
		$this->submitCrawlUrls = $submitCrawlUrls;
		$this->downloadCrawlUrls = $downloadCrawlUrls;
		$this->incomingProcInstructions = $incomingProcInstructions;
		$this->incomingConfigurationSelection = $configurationSelection;

		$this->duplicateTrack = array();
		$this->downloadUrls = array();

			// Drawing tree:
			/* @var $tree t3lib_pageTree */
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$tree->init('AND ' . $perms_clause);

		$pageinfo = t3lib_BEfunc::readPageAccess($id, $perms_clause);

			// Set root row:
		$tree->tree[] = Array(
			'row' => $pageinfo,
			'HTML' => '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], t3lib_iconWorks::getIcon('pages', $this->pObj->pageinfo)) . ' align="top" class="c-recIcon" alt="" />'
		);

			// Get branch beneath:
		if ($depth)	{
			$tree->getTree($id, $depth, '');
		}

			// Traverse page tree:
		$code = '';

		foreach ($tree->tree as $data) {

			$this->MP = false;

				// recognize mount points
			if($data['row']['doktype'] == 7){
				$mountpage = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'pages', 'uid = '.$data['row']['uid']);

					// fetch mounted pages
				$this->MP = $mountpage[0]['mount_pid'].'-'.$data['row']['uid'];

				$mountTree = t3lib_div::makeInstance('t3lib_pageTree');
				$mountTree->init('AND '.$perms_clause);
				$mountTree->getTree($mountpage[0]['mount_pid'], $depth, '');

				foreach($mountTree->tree as $mountData)	{
					$code .= $this->drawURLs_addRowsForPage(
						$mountData['row'],
						$mountData['HTML'].t3lib_BEfunc::getRecordTitle('pages',$mountData['row'],TRUE)
					);
				}

					// replace page when mount_pid_ol is enabled
				if($mountpage[0]['mount_pid_ol']){
					$data['row']['uid'] = $mountpage[0]['mount_pid'];
				} else {
						// if the mount_pid_ol is not set the MP must not be used for the mountpoint page
					$this->MP = false;
				}
			}

			$code .= $this->drawURLs_addRowsForPage(
				$data['row'],
				$data['HTML'] . t3lib_BEfunc::getRecordTitle('pages', $data['row'], TRUE)
			);
		}

		return $code;
	}


	/**
	 * Expand exclude string
	 *
	 * @param string exclude string
	 * @return array array of page ids
	 */
	public function expandExcludeString($excludeString) {

			// internal static caches;
		static $expandedExcludeStringCache;
		static $treeCache;

		if (empty($expandedExcludeStringCache[$excludeString])) {
			$pidList = array();
			if (!empty($excludeString)) {

					/* @var $tree t3lib_pageTree */
				$tree = t3lib_div::makeInstance('t3lib_pageTree');
				$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
				$tree->init('AND ' . $perms_clause);

				$excludeParts = t3lib_div::trimExplode(',', $excludeString);
				foreach ($excludeParts as $excludePart) {
					list($pid, $depth) = t3lib_div::trimExplode('+', $excludePart);

						// default is "page only" = "depth=0"
					if (empty($depth)) {
						$depth = ( stristr($excludePart,'+')) ? 99 : 0;
					}

					$pidList[] = $pid;

					if ($depth > 0) {
						if (empty($treeCache[$pid][$depth])) {
							$tree->getTree($pid, $depth);
							$treeCache[$pid][$depth] = $tree->tree;
						}
						foreach ($treeCache[$pid][$depth] as $data) {
							$pidList[] = $data['row']['uid'];
						}
					}
				}
			}
			$expandedExcludeStringCache[$excludeString] = array_unique($pidList);
		}
		return $expandedExcludeStringCache[$excludeString];
	}

	/**
	 * Create the rows for display of the page tree
	 * For each page a number of rows are shown displaying GET variable configuration
	 *
	 * @param	array		Page row
	 * @param	string		Page icon and title for row
	 * @return	string		HTML <tr> content (one or more)
	 */
	public function drawURLs_addRowsForPage(array $pageRow, $pageTitleAndIcon)	{

		$skipMessage = '';

			// Get list of configurations
		$configurations = $this->getUrlsForPageRow($pageRow, $skipMessage);

		if (count($this->incomingConfigurationSelection) > 0) {
				// 	remove configuration that does not match the current selection
			foreach ($configurations as $confKey => $confArray) {
				if (!in_array($confKey, $this->incomingConfigurationSelection)) {
					unset($configurations[$confKey]);
				}
			}
		}

			// Traverse parameter combinations:
		$c = 0;
		$cc = 0;
		$content = '';
		if (count($configurations)) {
			foreach($configurations as $confKey => $confArray)	{

					// Title column:
				if (!$c) {
					$titleClm = '<td rowspan="'.count($configurations).'">'.$pageTitleAndIcon.'</td>';
				} else {
					$titleClm = '';
				}


				if (!in_array($pageRow['uid'], $this->expandExcludeString($confArray['subCfg']['exclude']))) {

						// URL list:
					$urlList = $this->urlListFromUrlArray(
						$confArray,
						$pageRow,
						$this->scheduledTime,
						$this->reqMinute,
						$this->submitCrawlUrls,
						$this->downloadCrawlUrls,
						$this->duplicateTrack,
						$this->downloadUrls,
						$this->incomingProcInstructions // if empty the urls won't be filtered by processing instructions
					);

						// Expanded parameters:
					$paramExpanded = '';
					$calcAccu = array();
					$calcRes = 1;
					foreach($confArray['paramExpanded'] as $gVar => $gVal)	{
						$paramExpanded.= '
							<tr>
								<td class="bgColor4-20">'.htmlspecialchars('&'.$gVar.'=').'<br/>'.
												'('.count($gVal).')'.
												'</td>
								<td class="bgColor4" nowrap="nowrap">'.nl2br(htmlspecialchars(implode(chr(10),$gVal))).'</td>
							</tr>
						';
						$calcRes*= count($gVal);
						$calcAccu[] = count($gVal);
					}
					$paramExpanded = '<table class="lrPadding c-list param-expanded">'.$paramExpanded.'</table>';
					$paramExpanded.= 'Comb: '.implode('*',$calcAccu).'='.$calcRes;

						// Options
					$optionValues = '';
					if ($confArray['subCfg']['userGroups'])	{
						$optionValues.='User Groups: '.$confArray['subCfg']['userGroups'].'<br/>';
					}
					if ($confArray['subCfg']['baseUrl'])	{
						$optionValues.='Base Url: '.$confArray['subCfg']['baseUrl'].'<br/>';
					}
					if ($confArray['subCfg']['procInstrFilter'])	{
						$optionValues.='ProcInstr: '.$confArray['subCfg']['procInstrFilter'].'<br/>';
					}

						// Compile row:
					$content .= '
						<tr class="bgColor'.($c%2 ? '-20':'-10') . '">
							'.$titleClm.'
							<td>'.htmlspecialchars($confKey).'</td>
							<td>'.nl2br(htmlspecialchars(rawurldecode(trim(str_replace('&',chr(10).'&',t3lib_div::implodeArrayForUrl('',$confArray['paramParsed'])))))).'</td>
							<td>'.$paramExpanded.'</td>
							<td nowrap="nowrap">'.$urlList.'</td>
							<td nowrap="nowrap">'.$optionValues.'</td>
							<td nowrap="nowrap">'.((version_compare(TYPO3_version, '4.5.0', '<')) ? t3lib_div::view_array($confArray['subCfg']['procInstrParams.']) :  t3lib_utility_Debug::viewArray($confArray['subCfg']['procInstrParams.'])).'</td>
						</tr>';
				} else {

					$content .= '<tr class="bgColor'.($c%2 ? '-20':'-10') . '">
							'.$titleClm.'
							<td>'.htmlspecialchars($confKey).'</td>
							<td colspan="5"><em>No entries</em> (Page is excluded in this configuration)</td>
						</tr>';

				}


				$c++;
			}
		} else {
			$message = !empty($skipMessage) ? ' ('.$skipMessage.')' : '';

				// Compile row:
			$content.= '
				<tr class="bgColor-20" style="border-bottom: 1px solid black;">
					<td>'.$pageTitleAndIcon.'</td>
					<td colspan="6"><em>No entries</em>'.$message.'</td>
				</tr>';
		}

		return $content;
	}

	/**
	 *
	 * @return int
	 */
	function getUnprocessedItemsCount() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'count(*) as num',
					'tx_crawler_queue',
					'exec_time=0
					AND process_scheduled= 0
					AND scheduled<='.$this->getCurrentTime()
		);

		$count = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $count['num'];
	}








	/*****************************
	 *
	 * CLI functions
	 *
	 *****************************/

	/**
	 * Main function for running from Command Line PHP script (cron job)
	 * See ext/crawler/cli/crawler_cli.phpsh for details
	 *
	 * @return	int number of remaining items or false if error
	 */
	function CLI_main($altArgv=array())	{

		$this->setAccessMode('cli');
		$result = self::CLI_STATUS_NOTHING_PROCCESSED;

		$cliObj = t3lib_div::makeInstance('tx_crawler_cli');

		if (isset($cliObj->cli_args['-h']) || isset($cliObj->cli_args['--help']))	{
			$cliObj->cli_validateArgs();
			$cliObj->cli_help();
			exit;
		}

		if (!$this->getDisabled() && $this->CLI_checkAndAcquireNewProcess($this->CLI_buildProcessId())) {

			$countInARun = $cliObj->cli_argValue('--countInARun') ? intval($cliObj->cli_argValue('--countInARun')) : $this->extensionSettings['countInARun'];
				//Seconds
			$sleepAfterFinish = $cliObj->cli_argValue('--sleepAfterFinish') ? intval($cliObj->cli_argValue('--sleepAfterFinish')) : $this->extensionSettings['sleepAfterFinish'];
				//Milliseconds
			$sleepTime = $cliObj->cli_argValue('--sleepTime') ? intval($cliObj->cli_argValue('--sleepTime')) : $this->extensionSettings['sleepTime'];

			try {
				// Run process:
				$result = $this->CLI_run($countInARun, $sleepTime, $sleepAfterFinish);
			}
			catch (Exception $e) {
				$result = self::CLI_STATUS_ABORTED;
			}

				// Cleanup
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_crawler_process', 'assigned_items_count = 0');

				//TODO can't we do that in a clean way?
			$releaseStatus = $this->CLI_releaseProcesses($this->CLI_buildProcessId());

			$this->CLI_debug("Unprocessed Items remaining:".$this->getUnprocessedItemsCount()." (".$this->CLI_buildProcessId().")");
			$result |= ( $this->getUnprocessedItemsCount() > 0 ? self::CLI_STATUS_REMAIN : self::CLI_STATUS_NOTHING_PROCCESSED );
		} else {
			$result |= self::CLI_STATUS_ABORTED;
		}
		return $result;
	}

	/**
	 * Function executed by crawler_im.php cli script.
	 *
	 * @return	void
	 */
	function CLI_main_im()	{
		$this->setAccessMode('cli_im');

		$cliObj = t3lib_div::makeInstance('tx_crawler_cli_im');

			// Force user to admin state and set workspace to "Live":
		$GLOBALS['BE_USER']->user['admin'] = 1;
		$GLOBALS['BE_USER']->setWorkspace(0);

			// Print help
		if (!isset($cliObj->cli_args['_DEFAULT'][1]))	{
			$cliObj->cli_validateArgs();
			$cliObj->cli_help();
			exit;
		}

		$cliObj->cli_validateArgs();

		if ($cliObj->cli_argValue('-o')==='exec')	{
			$this->registerQueueEntriesInternallyOnly=TRUE;
		}

		$pageId = tx_crawler_api::forceIntegerInRange($cliObj->cli_args['_DEFAULT'][1],0);

		$configurationKeys  = $this->getConfigurationKeys($cliObj);

		if(!is_array($configurationKeys)){
			$configurations = $this->getUrlsForPageId($pageId);
			if(is_array($configurations)){
				$configurationKeys = array_keys($configurations);
			}else{
				$configurationKeys = array();
			}
		}

		if($cliObj->cli_argValue('-o')==='queue' || $cliObj->cli_argValue('-o')==='exec'){

			$reason = new tx_crawler_domain_reason();
			$reason->setReason(tx_crawler_domain_reason::REASON_GUI_SUBMIT);
			$reason->setDetailText('The cli script of the crawler added to the queue');
			tx_crawler_domain_events_dispatcher::getInstance()->post(	'invokeQueueChange',
				$this->setID,
				array(	'reason' => $reason )
			);
		}

		$this->setID = t3lib_div::md5int(microtime());
		$this->getPageTreeAndUrls(
			tx_crawler_api::forceIntegerInRange($cliObj->cli_args['_DEFAULT'][1],0),
			tx_crawler_api::forceIntegerInRange($cliObj->cli_argValue('-d'),0,99),
			$this->getCurrentTime(),
			tx_crawler_api::forceIntegerInRange($cliObj->cli_isArg('-n') ? $cliObj->cli_argValue('-n') : 30,1,1000),
			$cliObj->cli_argValue('-o')==='queue' || $cliObj->cli_argValue('-o')==='exec',
			$cliObj->cli_argValue('-o')==='url',
			t3lib_div::trimExplode(',',$cliObj->cli_argValue('-proc'),1),
			$configurationKeys
		);



		if ($cliObj->cli_argValue('-o')==='url')	{
			$cliObj->cli_echo(implode(chr(10),$this->downloadUrls).chr(10),1);
		} elseif ($cliObj->cli_argValue('-o')==='exec')	{
			$cliObj->cli_echo("Executing ".count($this->urlList)." requests right away:\n\n");
			$cliObj->cli_echo(implode(chr(10),$this->urlList).chr(10));
			$cliObj->cli_echo("\nProcessing:\n");

			foreach($this->queueEntries as $queueRec)	{
				$p = unserialize($queueRec['parameters']);
				#print_r($p);
				$cliObj->cli_echo($p['url'].' ('.implode(',',$p['procInstructions']).') => ');

				$result = $this->readUrlFromArray($queueRec);

				$requestResult = unserialize($result['content']);
				if (is_array($requestResult))	{
					$resLog = is_array($requestResult['log']) ?  chr(10).chr(9).chr(9).implode(chr(10).chr(9).chr(9),$requestResult['log']) : '';
					$cliObj->cli_echo('OK: '.$resLog.chr(10));
				} else {
					$cliObj->cli_echo('Error checking Crawler Result: '.substr(preg_replace('/\s+/',' ',strip_tags($result['content'])),0,30000).'...'.chr(10));
				}
			}
		} elseif ($cliObj->cli_argValue('-o')==='queue')	{
			$cliObj->cli_echo("Putting ".count($this->urlList)." entries in queue:\n\n");
			$cliObj->cli_echo(implode(chr(10),$this->urlList).chr(10));
		} else {
			$cliObj->cli_echo(count($this->urlList)." entries found for processing. (Use -o to decide action):\n\n",1);
			$cliObj->cli_echo(implode(chr(10),$this->urlList).chr(10),1);
		}
	}


	/**
	 * Function executed by crawler_im.php cli script.
	 *
	 * @return	void
	 */
	function CLI_main_flush($altArgv=array())	{

		$result = true;

		$this->setAccessMode('cli_flush');

		$cliObj = t3lib_div::makeInstance('tx_crawler_cli_flush');

			// Force user to admin state and set workspace to "Live":
		$GLOBALS['BE_USER']->user['admin'] = 1;
		$GLOBALS['BE_USER']->setWorkspace(0);

			// Print help
		if (!isset($cliObj->cli_args['_DEFAULT'][1]))	{
			$cliObj->cli_validateArgs();
			$cliObj->cli_help();
			exit;
		}

		$cliObj->cli_validateArgs();

		$pageId = tx_crawler_api::forceIntegerInRange($cliObj->cli_args['_DEFAULT'][1],0);

		$fullFlush = ($pageId == 0);

		$mode = $cliObj->cli_argValue('-o');
		switch($mode) {
			case 'all':
				$result = $this->getLogEntriesForPageId($pageId, '', true, $fullFlush);
				break;
			case 'finished':
			case 'pending':
				$result = $this->getLogEntriesForPageId($pageId, $mode, true, $fullFlush);
				break;
			default:
				$cliObj->cli_validateArgs();
				$cliObj->cli_help();
				$result = false;
		}
		return $result!==false;
	}

	/**
	 * Obtains configuration keys from the CLI arguments
	 *
	 * @param	tx_crawler_cli_im	$cliObj	Command line object
	 * @return	mixed	Array of keys or null if no keys found
	 */
	protected function getConfigurationKeys(tx_crawler_cli_im &$cliObj) {
		$parameter = trim($cliObj->cli_argValue('-conf'));

		return ($parameter != '' ? t3lib_div::trimExplode(',', $parameter) : array());
	}

	/**
	 * Running the functionality of the CLI (crawling URLs from queue)
	 *
	 * @return	string		Status message
	 */
	public function CLI_run($countInARun, $sleepTime, $sleepAfterFinish)	{
		$result = 0;
		$counter = 0;

			// First, run hooks:
		$this->CLI_runHooks();

			// Clean up the queue
		if (intval($this->extensionSettings['purgeQueueDays']) > 0) {
			$purgeDate = $this->getCurrentTime() - 24 * 60 * 60 * intval($this->extensionSettings['purgeQueueDays']);
			$del = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'tx_crawler_queue',
				'exec_time!=0 AND exec_time<' . $purgeDate
			);
		}

			// Select entries:
			//TODO Shouldn't this reside within the transaction?
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'qid,scheduled',
			'tx_crawler_queue',
			'exec_time=0
				AND process_scheduled= 0
				AND scheduled<='.$this->getCurrentTime(),
			'',
			'scheduled, qid',
		intval($countInARun)
		);

		if (count($rows)>0) {
			$quidList = array();

			foreach($rows as $r) {
				$quidList[] = $r['qid'];
			}

			$processId = $this->CLI_buildProcessId();

				//reserve queue entrys for process
			$GLOBALS['TYPO3_DB']->sql_query('BEGIN');
				//TODO make sure we're not taking assigned queue-entires
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_crawler_queue',
				'qid IN ('.implode(',',$quidList).')',
				array(
					'process_scheduled' => intval($this->getCurrentTime()),
					'process_id' => $processId
				)
			);

				//save the number of assigned queue entrys to determine who many have been processed later
			$numberOfAffectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tx_crawler_process',
				"process_id = '".$processId."'" ,
				array(
					'assigned_items_count' => intval($numberOfAffectedRows)
				)
			);

			if($numberOfAffectedRows == count($quidList)) {
				$GLOBALS['TYPO3_DB']->sql_query('COMMIT');
			} else  {
				$GLOBALS['TYPO3_DB']->sql_query('ROLLBACK');
				$this->CLI_debug("Nothing processed due to multi-process collision (".$this->CLI_buildProcessId().")");
				return ( $result | self::CLI_STATUS_ABORTED );
			}



			foreach($rows as $r)	{
				$result |= $this->readUrl($r['qid']);

				$counter++;
				usleep(intval($sleepTime));	// Just to relax the system

					// if during the start and the current read url the cli has been disable we need to return from the function
					// mark the process NOT as ended.
				if ($this->getDisabled()) {
					return ( $result | self::CLI_STATUS_ABORTED );
				}

				if (!$this->CLI_checkIfProcessIsActive($this->CLI_buildProcessId())) {
					$this->CLI_debug("conflict / timeout (".$this->CLI_buildProcessId().")");

						//TODO might need an additional returncode
					$result |= self::CLI_STATUS_ABORTED;
					break;		//possible timeout
				}
			}

			sleep(intval($sleepAfterFinish));

			$msg = 'Rows: '.$counter;
			$this->CLI_debug($msg." (".$this->CLI_buildProcessId().")");

		} else {
			$this->CLI_debug("Nothing within queue which needs to be processed (".$this->CLI_buildProcessId().")");
		}

		if($counter > 0) {
			$result |= self::CLI_STATUS_PROCESSED;
		}

		return $result;
	}

	/**
	 * Activate hooks
	 *
	 * @return	void
	 */
	function CLI_runHooks()	{
		global $TYPO3_CONF_VARS;
		if (is_array($TYPO3_CONF_VARS['EXTCONF']['crawler']['cli_hooks']))	{
			foreach($TYPO3_CONF_VARS['EXTCONF']['crawler']['cli_hooks'] as $objRef)	{
				$hookObj = &t3lib_div::getUserObj($objRef);
				if (is_object($hookObj))	{
					$hookObj->crawler_init($this);
				}
			}
		}
	}

	/**
	 * Try to aquire a new process with the given id
	 * also performs some auto-cleanup for orphan processes
	 * @todo preemption might not be the most elegant way to clean up
	 *
	 * @param string  identification string for the process
	 * @return boolean determines whether the attempt to get resources was successful
	 */
	function CLI_checkAndAcquireNewProcess($id) {

		$ret = true;

		$processCount = 0;
		$orphanProcesses = array();

		$GLOBALS['TYPO3_DB']->sql_query('BEGIN');

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'process_id,ttl',
			'tx_crawler_process',
			'active=1 AND deleted=0'
			);

			$currentTime = $this->getCurrentTime();

			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if ($row['ttl'] < $currentTime) {
					$orphanProcesses[] = $row['process_id'];
				} else {
					$processCount++;
				}
			}

				// if there are less than allowed active processes then add a new one
			if ($processCount < intval($this->extensionSettings['processLimit'])) {
				$this->CLI_debug("add ".$this->CLI_buildProcessId()." (".($processCount+1)."/".intval($this->extensionSettings['processLimit']).")");

					// create new process record
				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_crawler_process',
				array(
					'process_id' => $id,
					'active'=>'1',
					'ttl' => ($currentTime + intval($this->extensionSettings['processMaxRunTime']))
				)
				);

			} else {
				$this->CLI_debug("Processlimit reached (".($processCount)."/".intval($this->extensionSettings['processLimit']).")");
				$ret = false;
			}

			$this->CLI_releaseProcesses($orphanProcesses, true); // maybe this should be somehow included into the current lock

			$GLOBALS['TYPO3_DB']->sql_query('COMMIT');

			return $ret;
	}

	/**
	 * Release a process and the required resources
	 *
	 * @param  mixed   string with a single process-id or array with multiple process-ids
	 * @param boolean  show whether the DB-actions are included within an existings lock
	 * @return boolean
	 */
	function CLI_releaseProcesses($releaseIds, $withinLock=false) {

		if (!is_array($releaseIds)) {
			$releaseIds = array($releaseIds);
		}

		if (!count($releaseIds) > 0) {
			return false;   //nothing to release
		}

		if(!$withinLock) $GLOBALS['TYPO3_DB']->sql_query('BEGIN');

			// some kind of 2nd chance algo - this way you need at least 2 processes to have a real cleanup
			// this ensures that a single process can't mess up the entire process table

			// mark all processes as deleted which have no "waiting" queue-entires and which are not active
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_crawler_queue',
			'process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)',
			array(
				'process_scheduled' => 0,
			'process_id' => ''
			)
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_crawler_process',
			'active=0
			AND NOT EXISTS (
				SELECT * FROM tx_crawler_queue
				WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id
				AND tx_crawler_queue.exec_time = 0
			)',
			array(
				'deleted'=>'1'
			)
		);
				// mark all requested processes as non-active
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_crawler_process',
			'process_id IN (\''.implode('\',\'',$releaseIds).'\') AND deleted=0',
			array(
				'active'=>'0'
			)
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_crawler_queue',
			'exec_time=0 AND process_id IN ("'.implode('","',$releaseIds).'")',
			array(
				'process_scheduled'=>0,
				'process_id'=>''
			)
		);

		if(!$withinLock) $GLOBALS['TYPO3_DB']->sql_query('COMMIT');

		return true;
	}

	/**
	 * Check if there are still resources left for the process with the given id
	 * Used to determine timeouts and to ensure a proper cleanup if there's a timeout
	 *
	 * @param  string  identification string for the process
	 * @return boolean determines if the proccess is still active / has resources
	 */
	function CLI_checkIfProcessIsActive($pid) {

		$ret = false;
		$GLOBALS['TYPO3_DB']->sql_query('BEGIN');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'process_id,active,ttl',
			'tx_crawler_process','process_id = \''.$pid.'\'  AND deleted=0',
			'',
			'ttl',
			'0,1'
		);
		if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$ret = intVal($row['active'])==1;
		}
		$GLOBALS['TYPO3_DB']->sql_query('COMMIT');

		return $ret;
	}

	/**
	 *   Create a unique Id for the current process
	 *
	 *   @return string  the ID
	 */
	function CLI_buildProcessId() {
		if(!$this->processID) {
			$this->processID= t3lib_div::shortMD5(microtime(true));
		}
		return $this->processID;
	}

	/**
	 *   Prints a message to the stdout (only if debug-mode is enabled)
	 *
	 *   @param  string  the message
	 */
	function CLI_debug($msg) {
		if(intval($this->extensionSettings['processDebug'])) {
			echo $msg."\n"; flush();
		}
	}

	/**
	 * Set disabled status to prevent processes from being processed
	 *
	 * @param bool disabled (optional, defaults to true)
	 * @return void
	 */
	public function setDisabled($disabled=true) {
		if ($disabled) {
			t3lib_div::writeFile($this->processFilename, '');
		} else {
			if (is_file($this->processFilename)) {
				unlink($this->processFilename);
			}
		}
	}

	/**
	 * Get disable status
	 *
	 * @param void
	 * @return bool true if disabled
	 */
	public function getDisabled() {
		if (is_file($this->processFilename)) {
			return true;
		} else {
			return false;
		}
	}
}




if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_lib.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_lib.php']);
}
?>
