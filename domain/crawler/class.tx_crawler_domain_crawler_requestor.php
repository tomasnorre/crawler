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

require_once t3lib_extMgm::extPath('crawler').'domain/crawler/class.tx_crawler_domain_crawler_httpResponse.php';

/**
 * Url requestor
 * This object requests urls and returns httpResponse objects
 *
 * @author	Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @copyright Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version $Id: $
 * @since 2009-05-27
 * @package TYPO3
 * @subpackage crawler
 */
class tx_crawler_domain_crawler_requestor {

	/**
	 * Read the input URL by fsocket
	 *
	 * @param string URL to read
	 * @param string Crawler ID string (qid + hash to verify)
	 * @param integer (optional) Timeout time, default is 2 seconds
	 * @return tx_crawler_domain_crawler_httpResponse
	 * @throws tx_mvc_exception_invalidArgument
	 * @throws tx_mvc_exception if error while connecting occurs
	 */
	public static function requestUrl($url, $crawlerId, $timeout=2)	{
			// Parse URL, checking for scheme:
		$url = parse_url($url);

		if (!in_array($url['scheme'], array('', 'http'))) {
			throw new tx_mvc_exception_invalidArgument('Invalid scheme!');
		}

		$url['port'] = $url['port'] > 0 ? $url['port'] : 80;

		$errno = '';
		$errstr = '';

		$fp = fsockopen($url['host'], $url['port'], $errno, $errstr, $timeout);
		if ($fp === false)	{
			throw new tx_mvc_exception(sprintf('Error occurred while connection to host (Error number: "%s", Error string: "%s"', $errno, $errstr));
		}

		// Requesting...:

		// Request headers:
		$reqHeaders = array();
		$reqHeaders[] = 'GET '. $url['path'] . ($url['query'] ? '?' . $url['query'] : '') .' HTTP/1.0';
		$reqHeaders[] = 'Host: '.$url['host'];
//		$reqHeaders[] = 'Connection: keep-alive';
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
			if (($part === 'headers' && trim($line) === '') && !$isFirstLine) {
                   //switch to "content" part if empty row detected - tis should not be the first row of the response anyway
				$part = 'content';
			} elseif (($part==='headers') && stristr($line,'Content-Length:')) {
				$contentLength = intval(str_replace('Content-Length: ','',$line));
				if (TYPO3_DLOG) t3lib_div::devlog('crawler - Content-Length detected: '.$contentLength,__FUNCTION__);
				$d[$part][] = $line;
			} else {
				$d[$part][] = $line;

				if(($contentLength != -1) && ($contentLength <= strlen(implode('',(array)$d['content'])))) {
					if (TYPO3_DLOG) t3lib_div::devlog('crawler -stop reading URL- Content-Length reached',__FUNCTION__);
					break;
				}

			}
			$isFirstLine=FALSE;
		}
		fclose ($fp);

		$httpResponse = new tx_crawler_domain_crawler_httpResponse();
		$httpResponse->setHeader(implode('', $d['headers']));
		$httpResponse->setContent(implode('', (array)$d['content']));

		return $httpResponse;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/crawler/class.tx_crawler_domain_crawler_requestor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/domain/crawler/class.tx_crawler_domain_crawler_requestor.php']);
}

?>