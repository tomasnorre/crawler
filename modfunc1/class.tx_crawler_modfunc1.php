<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Module extension (addition to function menu) 'Site Crawler' for the 'crawler' extension.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   80: class tx_crawler_modfunc1 extends t3lib_extobjbase
 *  101:     function modMenu()
 *  133:     function main()
 *
 *              SECTION: Generate URLs for crawling:
 *  199:     function drawURLs()
 *  287:     function drawURLs_cfgSelectors()
 *  338:     function drawURLs_printTableHeader()
 *
 *              SECTION: Shows log of indexed URLs
 *  375:     function drawLog()
 *  547:     function drawLog_addRows($pageRow_setId,$titleString)
 *  664:     function drawLog_printTableHeader()
 *
 *              SECTION: CLI status display
 *  712:     function drawCLIstatus()
 *
 *              SECTION: General Helper Functions
 *  803:     function selectorBox($optArray, $name, $value, $multiple)
 *
 * TOTAL FUNCTIONS: 10
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require_once(PATH_t3lib.'class.t3lib_pagetree.php');
require_once(PATH_t3lib.'class.t3lib_extobjbase.php');

require_once(t3lib_extMgm::extPath('crawler').'class.tx_crawler_lib.php');

//
require_once t3lib_extMgm::extPath('crawler').'domain/process/class.tx_crawler_domain_process_repository.php';

require_once t3lib_extMgm::extPath('crawler').'view/process/class.tx_crawler_view_process_list.php';
require_once t3lib_extMgm::extPath('crawler').'view/class.tx_crawler_view_pagination.php';


/**
 * Crawler backend module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_crawler
 */
class tx_crawler_modfunc1 extends t3lib_extobjbase {

		// Internal, dynamic:
	var $duplicateTrack = array();
	var $submitCrawlUrls = FALSE;
	var $downloadCrawlUrls = FALSE;

	var $scheduledTime = 0;
	var $reqMinute = 0;
	var $incomingProcInstructions = array();

	/**
	 * @var tx_crawler_lib
	 */
	var $crawlerObj;
	var $CSVaccu = array();
	var $downloadUrls = array();


	/**
	 * Additions to the function menu array
	 *
	 * @return	array		Menu array
	 */
	function modMenu()	{
		global $LANG;

		return array (
			'depth' => array(
				0 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_0'),
				1 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_1'),
				2 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_2'),
				3 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_3'),
				4 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_4'),
				99 => $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.depth_infi'),
			),
			'crawlaction' => array(
				'start' => 'Start Crawling',
				'log' => 'Crawler log',
				'cli' => 'CLI status',
				'multiprocess' => 'Crawling Processes'
			),
			'log_resultLog' => '',
			'log_feVars' => '',
			'log_display' => array(
				'all' => 'All',
				'pending' => 'Pending',
				'finished' => 'Finished',
			)
		);
	}

	/**
	 * Main function
	 *
	 * @return	string		HTML output
	 */
	function main()	{
		global $LANG;

			// Set CSS styles specific for this document:
		$this->pObj->content = str_replace('/*###POSTCSSMARKER###*/','
			TABLE.c-list TR TD { white-space: nowrap; vertical-align: top; }
		',$this->pObj->content);

			// Type function menu:
		$h_func = t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[crawlaction]',$this->pObj->MOD_SETTINGS['crawlaction'],$this->pObj->MOD_MENU['crawlaction'],'index.php');

			// Showing depth-menu in certain cases:
		if ($this->pObj->MOD_SETTINGS['crawlaction']!=='cli' && ($this->pObj->MOD_SETTINGS['crawlaction']!=='log' || $this->pObj->id))	{
			$h_func.= t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[depth]',$this->pObj->MOD_SETTINGS['depth'],$this->pObj->MOD_MENU['depth'],'index.php');
		}

			// Additional menus for the log type:
		if ($this->pObj->MOD_SETTINGS['crawlaction']==='log')	{
			$h_func.= '<hr/>'.
					'Display: '.t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[log_display]',$this->pObj->MOD_SETTINGS['log_display'],$this->pObj->MOD_MENU['log_display'],'index.php','&setID='.t3lib_div::_GP('setID')).' - '.
					'Show Result Log: '.t3lib_BEfunc::getFuncCheck($this->pObj->id,'SET[log_resultLog]',$this->pObj->MOD_SETTINGS['log_resultLog'],'index.php','&setID='.t3lib_div::_GP('setID')).' - '.
					'Show FE Vars: '.t3lib_BEfunc::getFuncCheck($this->pObj->id,'SET[log_feVars]',$this->pObj->MOD_SETTINGS['log_feVars'],'index.php','&setID='.t3lib_div::_GP('setID'));
		}

		$theOutput.= $this->pObj->doc->spacer(5);
		$theOutput.= $this->pObj->doc->section($LANG->getLL('title'),$h_func,0,1);


			// Branch based on type:
		switch((string)$this->pObj->MOD_SETTINGS['crawlaction'])	{
			case 'start':
				$theOutput.= $this->pObj->doc->section('',$this->drawURLs(),0,1);
			break;
			case 'log':
				$theOutput.= $this->pObj->doc->section('',$this->drawLog(),0,1);
			break;
			case 'cli':
				$theOutput.= $this->pObj->doc->section('',$this->drawCLIstatus(),0,1);
			break;
			
			case 'multiprocess':
				$theOutput .= $this->pObj->doc->section('',$this->drawProcessOverviewAction(),0,1);
			break;
		}

		return $theOutput;
	}












	/*******************************
	 *
	 * Generate URLs for crawling:
	 *
	 ******************************/

	/**
	 * Produces a table with overview of the URLs to be crawled for each page
	 *
	 * @return	string		HTML output
	 */
	function drawURLs()	{
		global $BACK_PATH;

			// Init:
		$this->duplicateTrack = array();
		$this->submitCrawlUrls = t3lib_div::_POST('_crawl');
		$this->downloadCrawlUrls = t3lib_div::_POST('_download');

		switch((string)t3lib_div::_POST('tstamp'))	{
			case 'midnight':
				$this->scheduledTime = mktime(0,0,0);
			break;
			case '04:00':
				$this->scheduledTime = mktime(0,0,0)+4*3600;
			break;
			case 'now':
			default:
				$this->scheduledTime = time();
			break;
		}
		$this->reqMinute = t3lib_div::intInRange(t3lib_div::_POST('perminute'),1,10000);

		$this->incomingProcInstructions = t3lib_div::_POST('procInstructions');
		$this->incomingProcInstructions = is_array($this->incomingProcInstructions) ? $this->incomingProcInstructions : array('');

		$this->crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
		$this->crawlerObj->setID = t3lib_div::md5int(microtime());

		$code = $this->crawlerObj->getPageTreeAndUrls(
				$this->pObj->id,
				$this->pObj->MOD_SETTINGS['depth'],
				$this->scheduledTime,
				$this->reqMinute,
				$this->submitCrawlUrls,
				$this->downloadCrawlUrls,
				$this->incomingProcInstructions
			);
		$this->downloadUrls = $this->crawlerObj->downloadUrls;
		$this->duplicateTrack = $this->crawlerObj->duplicateTrack;

		$output = '';
		if ($code)	{

			$output .= '<h3>Crawl configuration:</h3>';
			$output .= '<input type="hidden" name="id" value="'.intval($this->pObj->id).'" />';

			if (!$this->submitCrawlUrls)	{
				$output .= '<input type="submit" name="_update" value="Update" /> ';
				$output .= '<input type="submit" name="_crawl" value="Crawl URLs" /> ';
				$output .= '<input type="submit" name="_download" value="Download URLs" /><br/>';
				$output .= '<br/>'.$this->drawURLs_cfgSelectors().'<br/>';
				$output .= 'Count: '.count(array_keys($this->duplicateTrack)).'<br/>';
				$output .= 'Current server time: '.date('H:i:s',time()).'<br/>';
				$output .= '<br/>
					<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">'.
						$this->drawURLs_printTableHeader().
						$code.
					'</table>';
			} else {
				$output .= count(array_keys($this->duplicateTrack)).' URLs submitted. <br/>';
				$output .= '<input type="submit" name="_" value="Continue" />';
			}
		}

			// Download Urls to crawl:
		if ($this->downloadCrawlUrls)	{

				// Creating output header:
			$mimeType = 'application/octet-stream';
			Header('Content-Type: '.$mimeType);
			Header('Content-Disposition: attachment; filename=CrawlerUrls.txt');

				// Printing the content of the CSV lines:
			echo implode(chr(13).chr(10),$this->downloadUrls);

				// Exits:
			exit;
		}

			// Return output:
		return 	$output;
	}

	/**
	 * Draws the configuration selectors for compiling URLs:
	 *
	 * @return	string		HTML table
	 */
	function drawURLs_cfgSelectors()	{

			// Processing Instructions:
		$pIs = array('' => '');
		foreach((array)$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] as $k => $v)	{
			$pIs[$k] = $v.' ['.$k.']';
		}
		$cell[] = $this->selectorBox($pIs, 'procInstructions', $this->incomingProcInstructions, 1);

			// Scheduled time:
		$cell[] = $this->selectorBox(array(
						'now' => 'Now',
						'midnight' => 'Midnight',
						'04:00' => '04:00 AM',
					),'tstamp',t3lib_div::_POST('tstamp'),0);

			// Requests per minute:
		$cell[] = $this->selectorBox(array(
						30 => '[Default]',
						1 => '1',
						5 => '5',
						10 => '10',
						20 => '20',
						30 => '30',
						50 => '50',
						100 => '100',
						200 => '200',
						1000 => '1000',
					),'perminute',t3lib_div::_POST('perminute'),0);

		$output = '
			<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">
				<tr class="bgColor5 tableheader">
					<td>Processing Instructions:</td>
					<td>Scheduled:</td>
					<td>Requests / Minute:</td>
				</tr>
				<tr class="bgColor4">
					<td valign="top">'.implode('</td>
					<td valign="top">', $cell).'</td>
				</tr>
			</table>';

		return $output;
	}

	/**
	 * Create Table header row for URL display
	 *
	 * @return	string		Table header
	 */
	function drawURLs_printTableHeader()	{

		$content = '
			<tr class="bgColor5 tableheader">
				<td>Page title:</td>
				<td>Key:</td>
				<td>Parameter Cfg:</td>
				<td>Values Expanded:</td>
				<td>URLs:</td>
				<td>Options:</td>
				<td>Parameters:</td>
			</tr>';

		return $content;
	}












	/*******************************
	 *
	 * Shows log of indexed URLs
	 *
	 ******************************/

	/**
	 * Shows the log of indexed URLs
	 *
	 * @return	string		HTML output
	 */
	function drawLog()	{
		global $BACK_PATH;

			// Init:
		$this->crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');
		$this->crawlerObj->setID = t3lib_div::md5int(microtime());

			// Read URL:
		if (t3lib_div::_GP('qid_read'))	{
			$this->crawlerObj->readUrl(t3lib_div::_GP('qid_read'),TRUE);
		}

			// Look for set ID sent - if it is, we will display contents of that set:
		$showSetId = t3lib_div::_GP('setID');

			// Show details:
		if (t3lib_div::_GP('qid_details'))	{

				// Get entry record:
			list($q_entry) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_crawler_queue','qid='.intval(t3lib_div::_GP('qid_details')));

				// Explode values:
			$q_entry['parameters'] = unserialize($q_entry['parameters']);
			$q_entry['result_data'] = unserialize($q_entry['result_data']);
			if (is_array($q_entry['result_data']))	{
				$q_entry['result_data']['content'] = unserialize($q_entry['result_data']['content']);
			}

				// Print rudimentary details:
			$output .= '
				<br/><br/>
				<input type="submit" value="Back" name="_back" />
				<input type="hidden" value="'.$this->pObj->id.'" name="id" />
				<input type="hidden" value="'.$showSetId.'" name="setID" />
				<br/>
				Current server time: '.date('H:i:s',time()).
				t3lib_div::view_array($q_entry);
		} else {	// Show list:

				// If either id or set id, show list:
			if ($this->pObj->id || $showSetId)	{
				if ($this->pObj->id)	{
						// Drawing tree:
					$tree = t3lib_div::makeInstance('t3lib_pageTree');
					$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
					$tree->init('AND '.$perms_clause);

						// Set root row:
					$HTML = '<img src="'.$BACK_PATH.t3lib_iconWorks::getIcon('pages',$this->pObj->pageinfo).'" width="18" height="16" align="top" class="c-recIcon" alt="" />';
					$tree->tree[] = Array(
						'row' => $this->pObj->pageinfo,
						'HTML' => $HTML
					);

						// Get branch beneath:
					if ($this->pObj->MOD_SETTINGS['depth'])	{
						$tree->getTree($this->pObj->id, $this->pObj->MOD_SETTINGS['depth'], '');
					}

						// Traverse page tree:
					$code = '';
					foreach($tree->tree as $data)	{
						$code.= $this->drawLog_addRows(
									$data['row'],
									$data['HTML'].t3lib_BEfunc::getRecordTitle('pages',$data['row'],TRUE)
								);
					}
				} else {
					$code = '';
					$code.= $this->drawLog_addRows(
								$showSetId,
								'Set ID: '.$showSetId
							);
				}

				$output = '';
				if ($code)	{

					$output .= '
						<br/><br/>
						<input type="submit" value="Reload list" name="_reload" />
						<input type="submit" value="Download entries as CSV" name="_csv" />
						<input type="submit" value="Flush entries" name="_flush" onclick="return confirm(\'Are you sure?\');" />
						<input type="hidden" value="'.$this->pObj->id.'" name="id" />
						<input type="hidden" value="'.$showSetId.'" name="setID" />
						<br/>
						Current server time: '.date('H:i:s',time()).'
						<br/><br/>


						<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">'.
							$this->drawLog_printTableHeader().
							$code.
						'</table>';
				}
			} else {	// Otherwise show available sets:
				$setList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
								'set_id, count(*) as count_value, scheduled',
								'tx_crawler_queue',
								'',
								'set_id',
								'scheduled DESC'
							);

				$code = '
					<tr class="bgColor5 tableheader">
						<td>Set ID:</td>
						<td>Count:</td>
						<td>Time:</td>
					</tr>
				';

				$cc=0;
				foreach($setList as $set)	{
					$code.= '
						<tr class="bgColor'.($cc%2 ? '-20':'-10').'">
							<td><a href="'.htmlspecialchars('index.php?setID='.$set['set_id']).'">'.$set['set_id'].'</a></td>
							<td>'.$set['count_value'].'</td>
							<td>'.t3lib_BEfunc::dateTimeAge($set['scheduled']).'</td>
						</tr>
					';

					$cc++;
				}

				$output .= '
					<br/><br/>
					<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">'.
						$code.
					'</table>';
			}
		}

			// Output to CSV file:
		if (t3lib_div::_POST('_csv'))	{

			$csvLines = array();

				// Field names:
			reset($this->CSVaccu);
			$fieldNames = array_keys(current($this->CSVaccu));
			$csvLines[] = t3lib_div::csvValues($fieldNames);

				// Data:
			foreach($this->CSVaccu as $row)	{
				$csvLines[] = t3lib_div::csvValues($row);
			}

				// Creating output header:
			$mimeType = 'application/octet-stream';
			Header('Content-Type: '.$mimeType);
			Header('Content-Disposition: attachment; filename=CrawlerLog.csv');

				// Printing the content of the CSV lines:
			echo implode(chr(13).chr(10),$csvLines);

				// Exits:
			exit;
		}

			// Return output
		return 	$output;
	}

	/**
	 * Create the rows for display of the page tree
	 * For each page a number of rows are shown displaying GET variable configuration
	 *
	 * @param	array		Page row or set-id
	 * @param	string		Title string
	 * @return	string		HTML <tr> content (one or more)
	 */
	function drawLog_addRows($pageRow_setId,$titleString)	{

			// If Flush button is pressed, flush tables instead of selecting entries:
		$doFlush = t3lib_div::_POST('_flush') ? TRUE : FALSE;
		$doCSV = t3lib_div::_POST('_csv') ? TRUE : FALSE;

			// Get result:
		if (is_array($pageRow_setId))	{
			$res = $this->crawlerObj->getLogEntriesForPageId($pageRow_setId['uid'], $this->pObj->MOD_SETTINGS['log_display'], $doFlush);
		} else {
			$res = $this->crawlerObj->getLogEntriesForSetId($pageRow_setId, $this->pObj->MOD_SETTINGS['log_display'], $doFlush);
		}

			// Init var:
		$colSpan = 9
				+ ($this->pObj->MOD_SETTINGS['log_resultLog'] ? -1 : 0)
				+ ($this->pObj->MOD_SETTINGS['log_feVars'] ? 3 : 0);

		if (count($res))	{
				// Traverse parameter combinations:
			$c = 0;
			$content='';
			foreach($res as $kk => $vv)	{

					// Title column:
				if (!$c)	{
					$titleClm = '<td rowspan="'.count($res).'">'.$titleString.'</td>';
				} else {
					$titleClm = '';
				}

					// Result:
				$resLog = '';
				if ($vv['result_data'])	{
					$requestContent = unserialize($vv['result_data']);
					$requestResult = unserialize($requestContent['content']);

					if (is_array($requestResult)) {
						if (empty($requestResult['errorlog'])) {
							$resStatus = 'OK';
						} else {
							$resStatus = implode("\n", $requestResult['errorlog']);
						}
						$resLog = is_array($requestResult['log']) ?  implode(chr(10),$requestResult['log']) : '';
					} else {
						$resStatus = 'Error: '.substr(ereg_replace('[[:space:]]+',' ',strip_tags($requestContent['content'])),0,100).'...';
					}
				} else {
					$resStatus = '..';
				}

					// Compile row:
				$parameters = unserialize($vv['parameters']);

					// Put data into array:
				$rowData = array();
				if ($this->pObj->MOD_SETTINGS['log_resultLog'])	{
					$rowData['result_log'] = $resLog;
				} else {
					$rowData['scheduled'] = t3lib_BEfunc::date($vv['scheduled']).' '.date('H:i:s',$vv['scheduled']);
					$rowData['exec_time'] = $vv['exec_time'] ? t3lib_BEfunc::date($vv['exec_time']).' '.date('H:i:s',$vv['exec_time']) : '-';
				}
				$rowData['result_status'] = $resStatus;
				$rowData['url'] = '<a href="'.htmlspecialchars($parameters['url']).'" target="_newWIndow">'.htmlspecialchars($parameters['url']).'</a>';
				$rowData['feUserGroupList'] = $parameters['feUserGroupList'];
				$rowData['procInstructions'] = is_array($parameters['procInstructions']) ? implode('; ',$parameters['procInstructions']) : '';
				$rowData['set_id'] = $vv['set_id'];

				if ($this->pObj->MOD_SETTINGS['log_feVars'])	{
					$rowData['tsfe_id'] = $requestResult['vars']['id'];
					$rowData['tsfe_gr_list'] = $requestResult['vars']['gr_list'];
					$rowData['tsfe_no_cache'] = $requestResult['vars']['no_cache'];
				}

					// Put rows together:
				$content.= '
					<tr class="bgColor'.($c%2 ? '-20':'-10').'">
						'.$titleClm.'
						<td><a href="index.php?id='.$this->pObj->id.'&qid_details='.$vv['qid'].'&setID='.t3lib_div::_GP('setID').'">'.htmlspecialchars($vv['qid']).'</a></td>
						<td><a href="index.php?id='.$this->pObj->id.'&qid_read='.$vv['qid'].'&setID='.t3lib_div::_GP('setID').'"><img src="'.$GLOBALS['BACK_PATH'].'gfx/refresh_n.gif" width="14" hspace="1" vspace="2" height="14" border="0" title="'.htmlspecialchars('Read').'" alt="" /></a></td>';
				foreach($rowData as $fKey => $value)	{

					if (t3lib_div::inList('url',$fKey))	{
						$content.= '
						<td>'.$value.'</td>';
					} else {
						$content.= '
						<td>'.nl2br(htmlspecialchars($value)).'</td>';
					}
				}
				$content.= '
					</tr>';
				$c++;

				if ($doCSV)	{
						// Only for CSV (adding qid and scheduled/exec_time if needed):
					$rowData['result_log'] = implode('// ',explode(chr(10),$resLog));
					$rowData['qid'] = $vv['qid'];
					$rowData['scheduled'] = t3lib_BEfunc::date($vv['scheduled']).' '.date('H:i:s',$vv['scheduled']);
					$rowData['exec_time'] = $vv['exec_time'] ? t3lib_BEfunc::date($vv['exec_time']).' '.date('H:i:s',$vv['exec_time']) : '-';
					$this->CSVaccu[] = $rowData;
				}
			}
		} else {

				// Compile row:
			$content.= '
				<tr class="bgColor-20">
					<td>'.$titleString.'</td>
					<td colspan="'.$colSpan.'"><em>No entries</em></td>
				</tr>';
		}

		return $content;
	}

	/**
	 * Create Table header row (log)
	 *
	 * @return	string		Table header
	 */
	function drawLog_printTableHeader()	{

		$content = '
			<tr class="bgColor5 tableheader">
				<td>Page Title:</td>
				<td>qid:</td>
				<td>&nbsp;</td>'.
				($this->pObj->MOD_SETTINGS['log_resultLog'] ? '
				<td>Result Log:</td>' : '
				<td>Scheduled:</td>
				<td>Run-time:</td>').'
				<td>Status:</td>
				<td>Url:</td>
				<td>Groups:</td>
				<td>Proc. Instr.:</td>
				<td>set_id:</td>'.
				($this->pObj->MOD_SETTINGS['log_feVars'] ? '
				<td>'.htmlspecialchars('TSFE->id').'</td>
				<td>'.htmlspecialchars('TSFE->gr_list').'</td>
				<td>'.htmlspecialchars('TSFE->no_cache').'</td>' : '').'
			</tr>';

		return $content;
	}













	/*****************************
	 *
	 * CLI status display
	 *
	 *****************************/

	/**
	 * Display status of CLI script
	 *
	 * @return	void
	 */
	function drawCLIstatus()	{

			// Init:
		$this->crawlerObj = t3lib_div::makeInstance('tx_crawler_lib');

			// Processing:
		if (t3lib_div::_POST('_run'))	{
			$this->crawlerObj->CLI_main();
		}
		if (t3lib_div::_POST('_enable'))	{
			$this->crawlerObj->CLI_setProcess('end', 'Status set by backend module');
		}
		if (t3lib_div::_POST('_disable'))	{
			$this->crawlerObj->CLI_setProcess('disabled', 'Status set by backend module');
		}

			// Create output:
		$dat = $this->crawlerObj->CLI_readProcessData();
		
		$view = new tx_crawler_view_cli_status();
		$view->setCliProcessData($dat);
		$view->setCurrentPageId($this->pObj->id);
		
	/*	$output = '
			<br/><br/>
			<table border="0" cellspacing="1" cellpadding="0" class="lrPadding c-list">
				<tr>
					<td class="bgColor5 tableheader">Status:</td>
					<td class="bgColor-20">'.htmlspecialchars($dat['status']).'</td>
				</tr>
				<tr>
					<td class="bgColor5 tableheader">Message:</td>
					<td class="bgColor-20">'.htmlspecialchars($dat['msg']).'</td>
				</tr>
				<tr>
					<td class="bgColor5 tableheader">Counter:</td>
					<td class="bgColor-20">'.htmlspecialchars($dat['counter']).'</td>
				</tr>
				<tr>
					<td class="bgColor5 tableheader">Last seen:</td>
					<td class="bgColor-20">'.htmlspecialchars(t3lib_BEfunc::dateTimeAge($dat['tstamp'])).'</td>
				</tr>
				<tr>
					<td class="bgColor5 tableheader">Last proc. time:</td>
					<td class="bgColor-20">'.htmlspecialchars($dat['endtime'] && $dat['starttime'] ? $dat['endtime']-$dat['starttime'] : '-').' seconds</td>
				</tr>
			</table>

			<br/>
			Current server time: '.date('H:i:s',time()).'
			<input type="hidden" value="'.$this->pObj->id.'" name="id" />
			<input type="submit" value="Reload" name="_" />
			';

		if ($dat['status']==='disabled')	{
			$output.= ' - <input type="submit" value="Enable" name="_enable" />';
		} else {
			$output.= ' - <input type="submit" value="Disable" name="_disable" />';
		}

		$output.= ' - <input type="submit" value="Run now" name="_run" />';

		$output.= '<br/><br/>Consider running the CLI script from shell: <br/>'.
					t3lib_extMgm::extPath('crawler').'cli/crawler_cli.phpsh'; */


		return $output;
	}


	/**
	 * This method is used to show an overview about the active an the finished crawling processes
	 * 
	 * @author Timo Schmidt
	 * @param void
	 * @return void
	 *
	 */
	protected function drawProcessOverviewAction(){
		global $BACK_PATH;
		$offset 	= intval(t3lib_div::_GP('offset'));
		$perpage 	= 20;
		
		$processRepository	= new tx_crawler_domain_process_repository();
		$allProcesses 		= $processRepository->findAll('ttl','DESC', $perpage, $offset);
		$allCount			= $processRepository->countAll();

		$listView			= new tx_crawler_view_process_list();
		$listView->setIconPath($BACK_PATH.'../typo3conf/ext/crawler/template/process/res/img/');
		$listView->setProcessCollection($allProcesses);
			
		$paginationView		= new tx_crawler_view_pagination();
		$paginationView->setCurrentOffset($offset);
		$paginationView->setPerPage($perpage);
		$paginationView->setTotalItemCount($allCount);
		
		return $listView->render().' <br />'.$paginationView->render();
	}

	/*****************************
	 *
	 * General Helper Functions
	 *
	 *****************************/

	/**
	 * Create selector box
	 *
	 * @param	array		Options key(value) => label pairs
	 * @param	string		Selector box name
	 * @param	string		Selector box value (array for multiple...)
	 * @param	boolean		If set, will draw multiple box.
	 * @return	string		HTML select element
	 */
	function selectorBox($optArray, $name, $value, $multiple)	{

		$options = array();
		foreach($optArray as $key => $val)	{
			$options[] = '
				<option value="'.htmlspecialchars($key).'"'.((!$multiple && !strcmp($value,$key)) || ($multiple && in_array($key,(array)$value))?' selected="selected"':'').'>'.htmlspecialchars($val).'</option>';
		}

		$output = '<select name="'.htmlspecialchars($name.($multiple?'[]':'')).'"'.($multiple ? ' multiple="multiple" size="'.count($options).'"' : '').'>'.implode('',$options).'</select>';

		return $output;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/modfunc1/class.tx_crawler_modfunc1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/modfunc1/class.tx_crawler_modfunc1.php']);
}
?>