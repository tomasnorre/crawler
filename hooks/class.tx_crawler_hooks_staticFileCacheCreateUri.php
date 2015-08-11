<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 AOE media (dev@aoemedia.de)
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
 *
 * @author: Michael Klapper <michael.klapper@aoemedia.de>
 * @date: 04.04.12
 * @time: 17:27
 */
class tx_crawler_hooks_staticFileCacheCreateUri  implements t3lib_Singleton {

	/**
	 * Initializes the variables before starting the processing.
	 *
	 * @param array $parameters The parameters used in this hook
	 * @param $parent The calling parent object
	 * @return void
	 */
	public function initialize(array $parameters, $parent) {
		list($parameters['host'], $parameters['uri']) = $this->fixNonSpeakingUri($parameters['host'], $parameters['uri'], $parameters['TSFE']);
	}

	/**
	 * Fixes non speaking URLs.
	 *
	 * @param	string $host
	 * @param	string $uri
	 * @param	tslib_fe $frontend
	 * @return	array
	 */
	protected function fixNonSpeakingUri($host, $uri, tslib_fe $frontend) {
		$matches = array();

		if ($this->isCrawlerExtensionRunning($frontend) && preg_match('#^/index.php\?&?id=(\d+)(&.*)?$#', $uri, $matches)) {
			$speakingUri = $frontend->cObj->typoLink_URL(array('parameter' => $matches[1], 'additionalParams' => $matches[2]));
			$speakingUriParts = parse_url($speakingUri);
			if(FALSE === $speakingUriParts){
				throw new Exception('Could not parse URI: ' . $speakingUri, 1289915976);
			}
			$speakingUrlPath = '/' . ltrim($speakingUriParts['path'], '/');
				// Don't change anything if speaking URL is part of old URI:
				// (it might be the case the using the speaking URL failed)
			if (strpos($uri, $speakingUrlPath) !== 0 || $speakingUrlPath === '/') {
				if (isset($speakingUriParts['host'])) {
					$host = $speakingUriParts['host'];
				}

				$uri = $speakingUrlPath;
			}
		}

		return array($host, $uri);
	}

	/**
	 * Determine whether the crawler extension is running and initiated the current request.
	 *
	 * @param tslib_fe $frontend
	 * @return boolean
	 */
	protected function isCrawlerExtensionRunning(tslib_fe $frontend) {
		return (
			t3lib_extMgm::isLoaded('crawler')
			&& isset($frontend->applicationData['tx_crawler']['running'])
			&& isset($frontend->applicationData['tx_crawler']['parameters']['procInstructions'])
			&& $frontend->applicationData['tx_crawler']['running']
		);
	}
}
