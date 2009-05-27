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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This class represents on item in the crawler queue.
 *
 * {@inheritdoc}
 *
 * class.tx_crawler_domain_queueEntry.php
 *
 * @author Timo Schmidt <schmidt@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: class.tx_crawler_domain_queueEntry.php $
 * @date 20.05.2008 11:34:53
 * @see tx_mvc_ddd_abstractDbObject
 * @category database
 * @package TYPO3
 * @subpackage crawler
 * @access public
 */
class tx_crawler_domain_queue_entry extends tx_mvc_ddd_abstractDbObject {
	/**
	 * Initialisize the database object with
	 * the table name of current object
	 *
	 * @access     public
	 * @return     string
	 */
	public static function getTableName() {
		return 'tx_crawler_queue';
	}
	
	/**
	 * This method is used to overwrite the default primary key
	 * in case of the crawler queue the primary key is qid
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @return string
	 */
	public static function getPrimaryKeyField() {
		return 'qid';
	}
	
	/**
	 * Returns an configuration object which is responsible for
	 * this queue entry.
	 * 
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @return tx_crawler_domain_configuration_configuration
	 */
	protected function getConfigurationObject(){

		if(!$this->row['configurationObject'] instanceof tx_crawler_domain_configuration_configuration){
			//if internal attribute is set retrieve from record 
			if(is_int($this->getConfiguration_id())){
				$configurationRepository = new tx_crawler_configuration_configurationRepository();
				$configurationObject = $configurationRepository->findById($this->getConfiguration_id());
				
				 $this->row['configurationObject'] = $configurationObject;
			}elseif(is_string( $this->getConfiguration_id() )){
				//else parse from page ts configm the configuration id is the key of the configuration in this case 
				$this->row['configurationObject'] = self::getCrawlerConfigurationObjectFromPageTsConfig($this->getPageid(), $this->getConfiguration_id());
			}
		}
		
		return $this->row['configurationObject'];
	}
	
	
	/**
	 * This method creates an instance of a crawler_configuration object from the page ts config
	 *
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @param int $page_id
	 * @param string $configuration_id id of the configuration subpart
	 * @return tx_crawler_domain_configuration_configuration
	 */
	protected static function getCrawlerConfigurationObjectFromPageTsConfig($page_id, $configuration_id){
		$ts_config	= t3lib_BEfunc::getPagesTSconfig($page_id);

		$configurationObject	= new tx_crawler_domain_configuration_configuration();
		
		if(is_array($ts_config)){
			$crawlerConfig 			= $ts_config['tx_crawler.']['crawlerCfg.']['paramSets.'];
			$configString 			= $crawlerConfig[$configuration_id];
			$subConfiguration 		= $crawlerConfig[$configuration_id.'.'];
			
			$configurationObject->setConfiguration($configString);
			
			if(is_array($subConfiguration)){
				$configurationObject->setPidsonly($subConfiguration['pidsOnly']);
				$configurationObject->setProcessing_instruction_filter(		$subConfiguration['procInstrFilter']);
				$configurationObject->setProcessing_instruction_parameters(	$subConfiguration['procInstrParams.']);
				$configurationObject->setBase_url($subConfiguration['baseUrl']);
			}	
		}
		
		
		return $configurationObject;
	}
	
	
	/**
	 * Returns a collection of urls which need to be crawled to complete this
	 * entry.
	 * 
	 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
	 * @return array
	 */
	public function getURLs(){
		$URLs = $this->compileUrls($this->getExpandedParameters(),array('?id='.$this->getPageId()));
		
		return $URLs;
	}
	
	/**
	 * Returns an array with expanded parameters
	 * 
	 * @author Fabrizio Branca und Timo Schmidt 
	 * @return array
	 */
	public function getExpandedParameters(){
		$crawlerConfiguration = $this->getConfigurationObject();

		tx_mvc_validator_factory::getInstanceValidator()->setClassOrInterface('tx_crawler_configuration_configuration')->isValid($crawlerConfiguration);
		
		$pidOnlyList = implode(',',t3lib_div::trimExplode(',',$crawlerConfiguration->getPidsOnly(),1));

		// process configuration if it is not page-specific or if the specific page is the current page:
		if (!strcmp($crawlerConfiguration->getPidsOnly(),'') || t3lib_div::inList($pidOnlyList,$this->getPageId())){

			// Explode, process etc.:
			$paramParsed = $this->parseParams($crawlerConfiguration->getConfiguration());
			$paramExpanded = $this->expandParameters($paramParsed,$this->getPageId());
		
			return $paramExpanded;
		}
	}
	
	/**
	 * Parse GET vars of input Query into array with key=>value pairs
	 *
	 * @param	string		Input query string
	 * @return	array		Keys are Get var names, values are the values of the GET vars.
	 */
	protected function parseParams($inputQuery)	{
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
	protected function expandParameters($paramArray,$pid)	{
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
	protected function compileUrls(array $paramArray, array $urls=array())	{

		if (count($paramArray))	{
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
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/queue/class.tx_crawler_domain_queueEntry.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/queue/class.tx_crawler_domain_queueEntry.php']);
}

?>