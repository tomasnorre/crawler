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
 * @author    Kasper Sk�rh�j <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  100: class tx_crawler_cli_im extends t3lib_cli
 *  107:     function tx_crawler_cli_im()
 *
 *
 *  138: class tx_crawler_lib
 *
 *              SECTION: Getting URLs based on Page TSconfig
 *  165:     function getUrlsForPageRow($pageRow)
 *  191:     function urlListFromUrlArray($vv,$pageRow,$scheduledTime,$reqMinute,$submitCrawlUrls,$downloadCrawlUrls,&$duplicateTrack,&$downloadUrls,$incomingProcInstructions)
 *  250:     function drawURLs_PIfilter($piString,$incomingProcInstructions)
 *  262:     function getUrlsForPageId($id)
 *  308:     function parseParams($inputQuery)
 *  337:     function expandParameters($paramArray,$pid)
 *  432:     function compileUrls($paramArray, $urls=array())
 *
 *              SECTION: Crawler log
 *  487:     function getLogEntriesForPageId($id,$filter='',$doFlush=FALSE)
 *  517:     function getLogEntriesForSetId($set_id,$filter='',$doFlush=FALSE)
 *  549:     function addQueueEntry_callBack($setId,$params,$callBack,$page_id=0,$schedule=0)
 *
 *              SECTION: URL setting
 *  592:     function addUrl($id,$url,$subCfg,$tstamp)
 *
 *              SECTION: URL reading
 *  650:     function readUrl($queueId, $force=FALSE)
 *  675:     function readUrlFromArray($field_array)
 *  697:     function readUrl_exec($queueRec)
 *  732:     function requestUrl($url, $crawlerId, $timeout=2)
 *
 *              SECTION: tslib_fe hooks:
 *  803:     function fe_init(&$params, $ref)
 *  826:     function fe_feuserInit(&$params, $ref)
 *  844:     function fe_isOutputting(&$params, $ref)
 *  857:     function fe_eofe(&$params, $ref)
 *
 *              SECTION: Compiling URLs to crawl - tools
 *  900:     function getPageTreeAndUrls($id,$depth,$scheduledTime,$reqMinute,$submitCrawlUrls,$downloadCrawlUrls,$incomingProcInstructions)
 *  951:     function drawURLs_addRowsForPage($pageRow,$pageTitleAndIcon)
 *
 *              SECTION: CLI functions
 * 1051:     function CLI_main()
 * 1076:     function CLI_set()
 * 1145:     function CLI_run()
 * 1179:     function CLI_runHooks()
 * 1197:     function CLI_checkProcess()
 * 1219:     function CLI_isDisabled()
 * 1235:     function CLI_readProcessData()
 * 1253:     function CLI_setProcess($status, $msg='')
 *
 * TOTAL FUNCTIONS: 30
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require_once(PATH_t3lib.'class.t3lib_pagetree.php');
require_once(PATH_t3lib.'class.t3lib_cli.php');
require_once(PATH_t3lib.'class.t3lib_tsparser.php');


/**
 * Cli basis:
 *
 * @author	Kasper Sk�rh�j <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_crawler
 */
class tx_crawler_cli_im extends t3lib_cli {

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function tx_crawler_cli_im()	{

			// Running parent class constructor
		parent::t3lib_cli();

			// Adding options to help archive:
		$this->cli_options[] = array('-proc listOfProcInstr', 'Comma list of processing instructions. These are the "actions" carried out when crawling and you must specify at least one. Depends on third-party extensions. Examples are "tx_cachemgm_recache" from "cachemgm" extension (will recache pages), "tx_staticpub_publish" from "staticpub" (publishing pages to static files) or "tx_indexedsearch_reindex" from "indexed_search" (indexes pages).');
		$this->cli_options[] = array('-d depth', 'Tree depth, 0-99', "How many levels under the 'page_id' to include.");
		$this->cli_options[] = array('-o mode', 'Output mode: "url", "exec", "queue"', "Specifies output modes\nurl : Will list URLs which wget could use as input.\nqueue: Will put entries in queue table.\nexec: Will execute all entries right away!");
		$this->cli_options[] = array('-n number', 'Number of items per minute.', 'Specifies how many items are put in the queue per minute. Only valid for output mode "queue"');
#		$this->cli_options[] = array('-v level', 'Verbosity level 0-3', "The value of level can be:\n  0 = all output\n  1 = info and greater (default)\n  2 = warnings and greater\n  3 = errors");

			// Setting help texts:
		$this->cli_help['name'] = 'crawler CLI interface -- Submitting URLs to be crawled via CLI interface.';
		$this->cli_help['synopsis'] = 'page_id ###OPTIONS###';
		$this->cli_help['description'] = "Works as a CLI interface to some functionality from the Web > Info > Site Crawler module; It can put entries in the queue from command line options, return the list of URLs and even execute all entries right away without having to queue them up - this can be useful for immediate re-cache, re-indexing or static publishing from command line.";
		$this->cli_help['examples'] = "/.../cli_dispatch.phpsh crawler_im 7 -d=2 -proc=tx_cachemgm_recache -o=exec\nWill re-cache pages from page 7 and two levels down, executed immediately.\n";
		$this->cli_help['examples'].= "/.../cli_dispatch.phpsh crawler_im 7 -d=0 -proc=tx_cachemgm_recache -n=4 -o=queue\nWill put entries for re-caching pages from page 7 into queue, 4 every minute.\n";
		$this->cli_help['author'] = "Kasper Skaarhoej, (c) 2007";
	}
}



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


	var $registerQueueEntriesInternallyOnly = array();
	var $queueEntries = array();
	var $urlList = array();

	var $debugMode=FALSE;

	var $extensionSettings=array();

	/************************************
	 *
	 * Getting URLs based on Page TSconfig
	 *
	 ************************************/

	function tx_crawler_lib() {
	    //read ext_em_conf_template settings and set
	    $this->extensionSettings=unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);
	    // print_r($this->extensionSettings);
	    //set defaults:
	    if ($this->extensionSettings['countInARun']=='') $this->extensionSettings['countInARun']=100;
	    if (!t3lib_div::intInRange($this->extensionSettings['processLimit'],1,99)) $this->extensionSettings['processLimit']=1;
	}

	/**
	 * Returns array with URLs to process for input page row, ignoring pages like doktype 3,4 and 200+
	 *
	 * @param	array		Page record with at least doktype and uid columns.
	 * @return	array		Result (see getUrlsForPageId())
	 * @see getUrlsForPageId()
	 */
	function getUrlsForPageRow($pageRow)	{

		if (!t3lib_div::inList('3,4',$pageRow['doktype']) && $pageRow['doktype']<200)	{
			$res = $this->getUrlsForPageId($pageRow['uid']);
		} else {
			$res = array();
		}

		return $res;
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
	 */
	function urlListFromUrlArray($vv,$pageRow,$scheduledTime,$reqMinute,$submitCrawlUrls,$downloadCrawlUrls,&$duplicateTrack,&$downloadUrls,$incomingProcInstructions)	{
		if (is_array($vv['URLs']))	{
			foreach($vv['URLs'] as $u)	{

				if ($this->drawURLs_PIfilter($vv['subCfg']['procInstrFilter'],$incomingProcInstructions))	{

						// Calculate cHash:
					$cHash_calc = '';
					if ($vv['subCfg']['cHash'])	{
						$pA = t3lib_div::cHashParams($u);
						if (count($pA)>1)	{
							$cHash_calc = t3lib_div::shortMD5(serialize($pA));
							$u.='&cHash='.rawurlencode($cHash_calc);
						}
					}

						// Create key by which to determine unique-ness:
					$uKey = $u.'|'.$vv['subCfg']['userGroups'].'|'.$vv['subCfg']['baseUrl'].'|'.$vv['subCfg']['procInstrFilter'];

						// Scheduled time:
					$schTime = $scheduledTime + round(count($duplicateTrack)*(60/$reqMinute));
					$schTime = floor($schTime/60)*60;

					if (isset($duplicateTrack[$uKey]))	{
						$urlList.='<em><span class="typo3-dimmed">'.htmlspecialchars($u).'</span></em><br/>';
					} else {
						$urlList.= '['.date('d-m H:i:s',$schTime).'] '.htmlspecialchars($u).'<br/>';
						$this->urlList[] = '['.date('d-m H:i:s',$schTime).'] '.$u;

							// Submit for crawling!
						if ($submitCrawlUrls)	{
							$this->addUrl(
								$pageRow['uid'],
								($vv['subCfg']['baseUrl'] ? $vv['subCfg']['baseUrl'] : t3lib_div::getIndpEnv('TYPO3_SITE_URL')).'index.php'.$u,
								$vv['subCfg'],
								$scheduledTime
							);
						} elseif ($downloadCrawlUrls)	{
							$theUrl = ($vv['subCfg']['baseUrl'] ? $vv['subCfg']['baseUrl'] : t3lib_div::getIndpEnv('TYPO3_SITE_URL')).'index.php'.$u;
							$downloadUrls[$theUrl] = $theUrl;
						}
					}
					$duplicateTrack[$uKey] = TRUE;
				}
			}
		} else {
			$urlList = 'ERROR';
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
	function drawURLs_PIfilter($piString,$incomingProcInstructions)	{
		foreach($incomingProcInstructions as $pi) {
			if (t3lib_div::inList($piString,$pi))	return TRUE;
		}
	}

	/**
	 * Compile array of URLs based on configuration parameters from Page TSconfig
	 *
	 * @param	integer		Page ID
	 * @return	array
	 */
	function getUrlsForPageId($id)	{

		/**
		 * Get configuration from tsConfig
		 */

		// Get page TSconfig for page ID:
		$pageTSconfig = t3lib_BEfunc::getPagesTSconfig($id);

		$res = array();

		if (is_array($pageTSconfig) && is_array($pageTSconfig['tx_crawler.']['crawlerCfg.']))	{
			$crawlerCfg = $pageTSconfig['tx_crawler.']['crawlerCfg.'];

			if (is_array($crawlerCfg['paramSets.']))	{
				$res = array();
				foreach($crawlerCfg['paramSets.'] as $key => $values)	{
					if (!is_array($values))	{

							// Sub configuration for a single configuration string:
						$subCfg = (array)$crawlerCfg['paramSets.'][$key.'.'];
						if (strcmp($subCfg['procInstrFilter'],''))	{
							$subCfg['procInstrFilter'] = implode(',',t3lib_div::trimExplode(',',$subCfg['procInstrFilter']));
						}
						$pidOnlyList = implode(',',t3lib_div::trimExplode(',',$subCfg['pidsOnly'],1));

							// process configuration if it is not page-specific or if the specific page is the current page:
						if (!strcmp($subCfg['pidsOnly'],'') || t3lib_div::inList($pidOnlyList,$id))	{

								// Explode, process etc.:
							$res[$key] = array();
							$res[$key]['subCfg'] = $subCfg;
							$res[$key]['paramParsed'] = $this->parseParams($values);
							$res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'],$id);
							$res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'],array('?id='.$id));
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
			$configurationRecordsForCurrentPage = t3lib_BEfunc::getRecordsByField('tx_crawler_configuration', 'pid', intval($page['uid']));

			if (is_array($configurationRecordsForCurrentPage)) {
				foreach ($configurationRecordsForCurrentPage as $configurationRecord) {

					$pidOnlyList = implode(',',t3lib_div::trimExplode(',',$configurationRecord['pidsonly'],1));

					// process configuration if it is not page-specific or if the specific page is the current page:
					if (!strcmp($configurationRecord['pidsOnly'],'') || t3lib_div::inList($pidOnlyList,$id)) {
						$key = $configurationRecord['name'];

						// don't overwrite previously defined paramSets
						if (!isset($res[$key])) {

							$TSparserObject = t3lib_div::makeInstance('t3lib_tsparser'); /* @var $TSparserObject t3lib_tsparser */
							$procInstrParams = $TSparserObject->parse($configurationRecord['processing_instruction_parameters_ts']);

							$subCfg = array(
								'procInstrFilter' => $configurationRecord['processing_instruction_filter'],
								'procInstrParams.' => $TSparserObject->setup,
								'baseUrl' => $configurationRecord['base_url'],
							);

							$res[$key] = array();
							$res[$key]['subCfg'] = $subCfg;
							$res[$key]['paramParsed'] = $this->parseParams($configurationRecord['configuration']);
							$res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $id);
							$res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], array('?id='.$id));
						}
					}
				}
			}
		}

		return $res;
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
	 * 		- "_TABLE:[TCA table name];[_PID:[optional page id, default is current page]]" = Look up of table records from PID, filtering out deleted records. Example "_TABLE:tt_content; _PID:123"
	 * 		- Default: Literal value
	 *
	 * @param	array		Array with key (GET var name) and values (value of GET var which is configuration for expansion)
	 * @param	integer		Current page ID
	 * @return	array		Array with key (GET var name) with the value being an array of all possible values for that key.
	 */
	function expandParameters($paramArray,$pid)	{
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

						// Look for integer range: (fx. 1-34 or -40--30)
					if (ereg('^(-?[0-9]+)[[:space:]]*-[[:space:]]*(-?[0-9]+)$',trim($pV),$reg))	{	// Integer range:

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

							$fieldName = $subpartParams['_FIELD'] ? $subpartParams['_FIELD'] : 'uid';
							if ($fieldName==='uid' || $TCA[$subpartParams['_TABLE']]['columns'][$fieldName])	{

								$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
											$fieldName,
											$subpartParams['_TABLE'],
											'pid='.intval($lookUpPid).
												t3lib_BEfunc::deleteClause($subpartParams['_TABLE']),
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
	 * @return	array		URLs accumulated, if number of urls exceed 10000 it will return false as an error!
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
					$newUrls[] = $url.
							(strcmp($val,'') ? '&'.rawurlencode($varName).'='.rawurlencode($val) : '');

						// Recursion brake:
					if (count($newUrls)>10000)	{
						$newUrls = FALSE;
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
	 * @return	array
	 */
	function getLogEntriesForPageId($id,$filter='',$doFlush=FALSE)	{

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
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_crawler_queue','page_id='.intval($id).$addWhere);
			return array();
		} else {
			return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_crawler_queue','page_id='.intval($id).$addWhere,'','scheduled');
		}
	}

	/**
	 * Return array of records from crawler queue for input set ID
	 *
	 * @param	integer		Set ID for which to look up log entries.
	 * @param	string		Filter: "all" => all entries, "pending" => all that is not yet run, "finished" => all complete ones
	 * @param	boolean		If TRUE, then entries selected at DELETED(!) instead of selected!
	 * @return	array
	 */
	function getLogEntriesForSetId($set_id,$filter='',$doFlush=FALSE)	{

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
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_crawler_queue','set_id='.intval($set_id).$addWhere);
			return array();
		} else {
			return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_crawler_queue','set_id='.intval($set_id).$addWhere,'','scheduled');
		}
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
			'page_id' => $page_id,
			'parameters' => serialize($params),
			'scheduled' => $schedule ? $schedule : time(),
			'exec_time' => 0,
			'set_id' => $setId,
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
	 * @return	void
	 */
	function addUrl($id,$url,$subCfg,$tstamp)	{
		global $TYPO3_CONF_VARS;

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
		$fieldArray = array(
			'page_id' => $id,
			'parameters' => serialize($parameters),
			'scheduled' => $tstamp,
			'exec_time' => 0,
			'set_id' => $this->setID,
			'result_data' => '',
		);


		if ($this->registerQueueEntriesInternallyOnly)	{
			$this->queueEntries[] = $fieldArray;
		} else {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_crawler_queue',$fieldArray);
		}
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
        if ($this->debugMode) t3lib_div::devlog('crawler-readurl start '.microtime(true),__FUNCTION__);
			// Get entry:
		list($queueRec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_crawler_queue','qid='.intval($queueId).($force ? '' : ' AND exec_time=0 AND process_scheduled > 0'));

		if (is_array($queueRec))	{
				// Set exec_time to lock record:
			$field_array = array('exec_time' => time());
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue','qid='.intval($queueId), $field_array);

			$result = $this->readUrl_exec($queueRec);

				// Set result in log which also denotes the end of the processing of this entry.
			$field_array = array('result_data' => serialize($result));
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue','qid='.intval($queueId), $field_array);
		}
        if ($this->debugMode) t3lib_div::devlog('crawler-readurl stop '.microtime(true),__FUNCTION__);
	}

	/**
	 * Read URL for not-yet-inserted log-entry
	 *
	 * @param	integer		Queue field array,
	 * @return	void
	 */
	function readUrlFromArray($field_array)	{

			// Set exec_time to lock record:
		$field_array['exec_time'] = time();
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
#		print_r($parameters);
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
	 * @return	array		Array with content
	 */
	function requestUrl($url, $crawlerId, $timeout=2)	{
			// Parse URL, checking for scheme:
		$url = parse_url($url);

		if(!in_array($url['scheme'],array('','http')))	return FALSE;

		$fp = fsockopen ($url['host'], ($url['port'] > 0 ? $url['port'] : 80), $errno, $errstr, $timeout);
		if (!$fp)	{
			return FALSE;
		} else {	// Requesting...:
				// Request headers:
			$reqHeaders = array();
			$reqHeaders[] = 'GET '.$url['path'].($url['query'] ? '?'.$url['query'] : '').' HTTP/1.0';
			$reqHeaders[] = 'Host: '.$url['host'];
//			$reqHeaders[] = 'Connection: keep-alive';
			$reqHeaders[] = 'Connection: close';
			$reqHeaders[] = 'X-T3crawler: '.$crawlerId;
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
                    //switch to "content" part if empty row detected - tis should not be the first row of the response anyway
					$part = 'content';
				} elseif(($part==='headers') && stristr($line,'Content-Length:')) {
					$contentLength = intval(str_replace('Content-Length: ','',$line));
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

				// Implode content and headers:
			$d['headers'] = implode('', $d['headers']);
			$d['content'] = implode('', (array)$d['content']);
				// Return
			return $d;
		}
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
						if(empty($params['pObj']->applicationData['tx_crawler']['log'][$pollable]['success'])) {
							$params['pObj']->applicationData['tx_crawler']['errorlog'][] = 'Error: pollable extension ('.$pollable.') did not complete successfully.';
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
	 * @return	string		HTML code
	 */
	function getPageTreeAndUrls($id,$depth,$scheduledTime,$reqMinute,$submitCrawlUrls,$downloadCrawlUrls,$incomingProcInstructions)	{
		global $BACK_PATH;
		global $LANG;
		if (!is_object($LANG)) {
			//echo $BACK_PATH.'typo3/sysext/lang/lang.php';
			include_once(PATH_typo3.'sysext/lang/lang.php');
			$LANG = t3lib_div::makeInstance('language');
			$LANG->init(0);
		}
		$this->scheduledTime = $scheduledTime;
		$this->reqMinute = $reqMinute;
		$this->submitCrawlUrls = $submitCrawlUrls;
		$this->downloadCrawlUrls = $downloadCrawlUrls;
		$this->incomingProcInstructions = $incomingProcInstructions;
		$this->duplicateTrack = array();
		$this->downloadUrls = array();

			// Drawing tree:
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$tree->init('AND '.$perms_clause);

		$pageinfo = t3lib_BEfunc::readPageAccess($id,$perms_clause);

			// Set root row:
		$HTML = '<img src="'.$BACK_PATH.t3lib_iconWorks::getIcon('pages',$pageinfo).'" width="18" height="16" align="top" class="c-recIcon" alt="" />';
		$tree->tree[] = Array(
			'row' => $pageinfo,
			'HTML' => $HTML
		);


			// Get branch beneath:
		if ($depth)	{
			$tree->getTree($id, $depth, '');
		}

			// Traverse page tree:
		$code = '';
		foreach($tree->tree as $data)	{
			$code.= $this->drawURLs_addRowsForPage(
						$data['row'],
						$data['HTML'].t3lib_BEfunc::getRecordTitle('pages',$data['row'],TRUE)
					);
		}

		return $code;
	}

	/**
	 * Create the rows for display of the page tree
	 * For each page a number of rows are shown displaying GET variable configuration
	 *
	 * @param	array		Page row
	 * @param	string		Page icon and title for row
	 * @return	string		HTML <tr> content (one or more)
	 */
	function drawURLs_addRowsForPage($pageRow, $pageTitleAndIcon)	{

			// Get list of URLs from page:
		$res = $this->getUrlsForPageRow($pageRow);

			// Traverse parameter combinations:
		$c = 0;
		$cc = 0;
		$content = '';
		if (count($res))	{
			foreach($res as $kk => $vv)	{

					// Title column:
				if (!$c)	{
					$titleClm = '<td rowspan="'.count($res).'">'.$pageTitleAndIcon.'</td>';
				} else {
					$titleClm = '';
				}

					// URL list:
				$urlList = $this->urlListFromUrlArray($vv,$pageRow,$this->scheduledTime,$this->reqMinute,$this->submitCrawlUrls,$this->downloadCrawlUrls,$this->duplicateTrack,$this->downloadUrls,$this->incomingProcInstructions);

					// Expanded parameters:
				$paramExpanded = '';
				$calcAccu = array();
				$calcRes = 1;
				foreach($vv['paramExpanded'] as $gVar => $gVal)	{
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
				$paramExpanded = '<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">'.$paramExpanded.'</table>';
				$paramExpanded.= 'Comb: '.implode('*',$calcAccu).'='.$calcRes;

					// Options
				$optionValues = '';
				if ($vv['subCfg']['userGroups'])	{
					$optionValues.='User Groups: '.$vv['subCfg']['userGroups'].'<br/>';
				}
				if ($vv['subCfg']['baseUrl'])	{
					$optionValues.='Base Url: '.$vv['subCfg']['baseUrl'].'<br/>';
				}
				if ($vv['subCfg']['procInstrFilter'])	{
					$optionValues.='ProcInstr: '.$vv['subCfg']['procInstrFilter'].'<br/>';
				}

					// Compile row:
				$content.= '
					<tr class="bgColor'.($c%2 ? '-20':'-10').'">
						'.$titleClm.'
						<td>'.htmlspecialchars($kk).'</td>
						<td>'.nl2br(htmlspecialchars(rawurldecode(trim(str_replace('&',chr(10).'&',t3lib_div::implodeArrayForUrl('',$vv['paramParsed'])))))).'</td>
						<td>'.$paramExpanded.'</td>
						<td nowrap="nowrap">'.$urlList.'</td>
						<td nowrap="nowrap">'.$optionValues.'</td>
						<td nowrap="nowrap">'.t3lib_div::view_array($vv['subCfg']['procInstrParams.']).'</td>
					</tr>';
				$c++;
			}
		} else {
				// Compile row:
			$content.= '
				<tr class="bgColor-20">
					<td>'.$pageTitleAndIcon.'</td>
					<td colspan="6"><em>No entries</em></td>
				</tr>';
		}

		return $content;
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
	 * @return	void
	 */
	function CLI_main()	{
        if ($this->debugMode) t3lib_div::devlog('crawler CLI_main -'.microtime(true),__FUNCTION__);
        $this->CLI_debug("creating process (".$this->CLI_buildProcessId().")");
		// Checking that no other CLI is running:
        if (($this->hasMultipleProcessSupport() && $this->CLI_checkAndAcquireNewProcess($this->CLI_buildProcessId())) || !$this->CLI_checkProcess())	{
				// Set "start" status (thus reserving to run!)
			$this->CLI_setProcess('start');
            $this->CLI_debug("process running (".$this->CLI_buildProcessId().")");
				// Run process:
			$msg = $this->CLI_run();
            $this->CLI_debug($msg." (".$this->CLI_buildProcessId().")");
				// End process (releasing process):
			if ($this->CLI_isDisabled())	{
				$this->CLI_setProcess('disabled', $msg);
			} else {
				$this->CLI_setProcess('end', $msg);
			}

            if($this->hasMultipleProcessSupport()) {
                $this->CLI_releaseProcesses($this->CLI_buildProcessId());
            }
            $this->CLI_debug("process released successful (".$this->CLI_buildProcessId().")");
		} else {
		    if ($this->debugMode) t3lib_div::devlog('crawler CLI_main - found another crawler process - nothing to do'.microtime(true),__FUNCTION__);
            $this->CLI_debug("no resources for processs (".$this->CLI_buildProcessId().")");
		}
	}

	/**
	 * Function executed by crawler_im.php cli script.
	 *
	 * @return	void
	 */
	function CLI_main_im()	{
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

		$this->setID = t3lib_div::md5int(microtime());
		$this->getPageTreeAndUrls(
			t3lib_div::intInRange($cliObj->cli_args['_DEFAULT'][1],0),
			t3lib_div::intInRange($cliObj->cli_argValue('-d'),0,99),
			time(),
			t3lib_div::intInRange($cliObj->cli_isArg('-n') ? $cliObj->cli_argValue('-n') : 30,1,1000),
			$cliObj->cli_argValue('-o')==='queue' || $cliObj->cli_argValue('-o')==='exec',
			$cliObj->cli_argValue('-o')==='url',
			t3lib_div::trimExplode(',',$cliObj->cli_argValue('-proc'),1)
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
					$cliObj->cli_echo('Error checking Crawler Result: '.substr(ereg_replace('[[:space:]]+',' ',strip_tags($result['content'])),0,30000).'...'.chr(10));
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
	 * Running the functionality of the CLI (crawling URLs from queue)
	 *
	 * @return	string		Status message
	 */
	function CLI_run()	{

			// First, run hooks:
		$this->CLI_runHooks();

			// Clean up the queue
		if (intval($this->extensionSettings['purgeQueueDays']) > 0) {
			$purgeDate = time() - 24 * 60 * 60 * intval($this->extensionSettings['purgeQueueDays']);
			$del = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'tx_crawler_queue',
				'exec_time!=0 AND exec_time<' . $purgeDate
			);
		}

			// Select entries:
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'qid,scheduled',
			'tx_crawler_queue',
			'exec_time=0
				AND process_scheduled= 0
                AND scheduled<='.time(),
			'',
			'scheduled, qid',
			intval($this->extensionSettings['countInARun']));

        if ($this->hasMultipleProcessSupport() && count($rows)>0) {
            $quidList = array();
            foreach($rows as $r) {
                $quidList[] = $r['qid'];
            }
            $GLOBALS['TYPO3_DB']->sql_query('BEGIN');
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue','qid IN ('.implode(',',$quidList).')',array('process_scheduled'=>time(),'process_id'=>$this->CLI_buildProcessId()));
            if($GLOBALS['TYPO3_DB']->sql_affected_rows() == count($quidList)) {
                $GLOBALS['TYPO3_DB']->sql_query('COMMIT');
            } else  {
                $GLOBALS['TYPO3_DB']->sql_query('ROLLBACK');
                return 'Nothing processed due to multi-process collision';
            }
        } else {
            print_r($rows);
        }

		$counter = 0;
		foreach($rows as $r)	{
			$this->readUrl($r['qid']);
			$counter++;
			usleep(intval($this->extensionSettings['sleepTime']));	// Just to relax the system

			if ($this->CLI_isDisabled())	break;
            if ($this->hasMultipleProcessSupport() && !$this->CLI_checkIfProcessIsActive($this->CLI_buildProcessId())) {
                    $this->CLI_debug("conflict / timeout (".$this->CLI_buildProcessId().")");
                    break;     //possible timeout
            }
		}
		sleep(intval($this->extensionSettings['sleepAfterFinish']));
		return 'Rows: '.$counter;
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
	 * Checking if another process is already running and didn't finish.
	 *
	 * @return	boolean		True if another process is running
	 */
	function CLI_checkProcess()	{

			// Read status data
		$dat = $this->CLI_readProcessData();

			// See if is set and set to end; if not, return last starttime.
		if ($dat['status'] && $dat['status']!=='end')	{
				// IF more than one hour has passed since last start, the process is probably stalled and we allow it to run:
				// todo: Could be to check the process list for occurences of this script name and use that as indication of a running, non-stalled process instead of time!
			if ($dat['status']==='start' && $dat['starttime']+$this->max_CLI_exec_time < time())	{
				return FALSE;
			} else {
				return TRUE;
			}
		}
	}

	/**
	 * Checking if CLI has been disabled from backend
	 *
	 * @return	boolean		True if disabled
	 */
	function CLI_isDisabled()	{

			// Read status data
		$dat = $this->CLI_readProcessData();

			// See if is set and set to end; if not, return last starttime.
		if ($dat['status'] && $dat['status']==='disabled')	{
			return TRUE;
		}
	}

	/**
	 * Reading data from process file
	 *
	 * @return	array		Process file data, empty array if didn't exist (or parseerror)
	 */
	function CLI_readProcessData()	{
			// Look for process file:
		$filePath = $this->singleProcessFileName();
		if (@is_file($filePath))	{

				// Read status and return it:
			$dat = t3lib_div::xml2array(t3lib_div::getUrl($filePath));
			return (array)$dat;
		} else return array();
	}

	/**
	 * Write process data
	 *
	 * @param	string		Process status ("start", "end" etc).
	 * @param	string		Status message to set.
	 * @return	void
	 */
	function CLI_setProcess($status, $msg='')	{

			// Get current
		$currentStatus = $this->CLI_readProcessData();

			// Initialize array:
		$statusArray = array(
			'status' => $status,
			'tstamp' => time(),
			'msg' => $msg,
			'counter' => $currentStatus['counter']
		);

			// Set other variables:
		switch((string)$status)	{
			case 'start':
				$statusArray['starttime'] = time();
				$statusArray['endtime'] = 0;
				$statusArray['counter']++;
			break;
			case 'end':
				$statusArray['starttime'] = $currentStatus['starttime'];
				$statusArray['endtime'] = time();
			break;
		}

			// Create XML:
		$xmlContent = t3lib_div::array2xml_cs($statusArray);

			// Save to file:
		$filePath = PATH_site.'typo3temp/tx_crawler.proc';
		t3lib_div::writeFile($filePath,$xmlContent);
	}

    /**
    *   Try to aquire a new process with the given id
    *   also performs some auto-cleanup for orphran processes
    *   only required for multiprocess-environments
    *
    *   @param  string  identification string for the process
    *   @return	boolean determines whether the attempt to get resources was successful
    */
    function CLI_checkAndAcquireNewProcess($id) {

        $ret = true;
        $processCount=0;
        $orphanProcesses=array();

        $GLOBALS['TYPO3_DB']->sql_query('BEGIN');
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('process_id,ttl','tx_crawler_process','active=1 AND deleted=0');
        while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
            if($row['ttl']<time()) {
                $orphranProcesses[] = $row['process_id'];
            } else {
                $processCount++;
            }
        }
        if($processCount < intval($this->extensionSettings['processLimit'])) {
            $this->CLI_debug("add ".$this->CLI_buildProcessId()." (".($processCount+1)."/".intval($this->extensionSettings['processLimit']).")");
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_crawler_process',array('process_id'=>$id,'active'=>'1','ttl'=>(time()+$this->extensionSettings['processMaxRunTime'])));
        } else {
            $ret = false;
        }
        $this->CLI_releaseProcesses($orphranProcesses,true);     // maybe this should be somehow included into the current lock


        $GLOBALS['TYPO3_DB']->sql_query('COMMIT');
        return $ret;
    }

    /**
    *   Release a process and the required resources
    *
    *   @param  mixed   string with a single process-id or array with multiple process-ids
    *   @param boolean  show whether the DB-actions are included within an existings lock
    *   @return void
    */
    function CLI_releaseProcesses($releaseIds,$withinLock=false) {

        if(!is_array($releaseIds)) {
            $releaseIds = array($releaseIds);
        }
        if(!count($releaseIds)>0) {
            return false;   //nothing to release
        }

        if(!$withinLock) $GLOBALS['TYPO3_DB']->sql_query('BEGIN');
        // some kind of 2nd chance algo - this way you need at least 2 processes to have a real cleanup
        // this ensures that a single process can't mess up the entire process table

        // mark all processes as deleted which have no "waiting" queue-entires and which are not active
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue','process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)',array('process_scheduled'=>0,'process_id'=>''));
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_process','active=0 AND NOT EXISTS (SELECT * FROM tx_crawler_queue  WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id AND tx_crawler_queue.exec_time = 0)',array('deleted'=>'1'));

        // mark all requested processes as non-active
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_process','process_id IN (\''.implode('\',\'',$releaseIds).'\') AND deleted=0',array('active'=>'0'));
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_crawler_queue','exec_time=0 AND process_id IN ("'.implode('","',$releaseIds).'")',array('process_scheduled'=>0,'process_id'=>''));
        if(!$withinLock) $GLOBALS['TYPO3_DB']->sql_query('COMMIT');
        return true;
    }

    /**
    *   Check if there are still resources left for the process with the given id
    *   Used to determine timeouts and to ensure a proper cleanup if there's a timeout
    *
    *   @param  string  identification string for the process
    *   @return boolean determines if the proccess is still active / has resources
    */
    function CLI_checkIfProcessIsActive($pid) {

        $ret = false;
        $GLOBALS['TYPO3_DB']->sql_query('BEGIN');
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('process_id,active,ttl','tx_crawler_process','process_id = \''.$pid.'\'  AND deleted=0','','ttl','0,1');
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
    *    Determines whether multiple proccesses are supported
    *
    *   @return boolean
    */
    function hasMultipleProcessSupport() {
        return (intval($this->extensionSettings['processLimit']) > 1);
    }

    /**
    *   Returns the filename where single-proccess crawlers store there information
    *
    *   @return string
    */
    function singleProcessFileName() {
        return PATH_site.'typo3temp/tx_crawler.proc';
    }
}




if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_lib.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_lib.php']);
}
?>