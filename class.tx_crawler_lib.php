<?php

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
 * Class tx_crawler_lib
 */
class tx_crawler_lib
{
    /**
     * @var integer
     */
    public $setID = 0;

    /**
     * @var string
     */
    public $processID = '';

    /**
     * One hour is max stalled time for the CLI
     * If the process had the status "start" for 3600 seconds, it will be regarded stalled and a new process is started
     *
     * @var integer
     */
    public $max_CLI_exec_time = 3600;

    /**
     * @var array
     */
    public $duplicateTrack = [];

    /**
     * @var array
     */
    public $downloadUrls = [];

    /**
     * @var array
     */
    public $incomingProcInstructions = [];

    /**
     * @var array
     */
    public $incomingConfigurationSelection = [];

    /**
     * @var array
     */
    public $registerQueueEntriesInternallyOnly = [];

    /**
     * @var array
     */
    public $queueEntries = [];

    /**
     * @var array
     */
    public $urlList = [];

    /**
     * @var boolean
     */
    public $debugMode = false;

    /**
     * @var array
     */
    public $extensionSettings = [];

    /**
     * Mount Point
     *
     * @var boolean
     */
    public $MP = false;

    /**
     * @var string
     */
    protected $processFilename;

    /**
     * Holds the internal access mode can be 'gui','cli' or 'cli_im'
     *
     * @var string
     */
    protected $accessMode;

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private $db;

    /**
     * @var TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    private $backendUser;

    const CLI_STATUS_NOTHING_PROCCESSED = 0;
    const CLI_STATUS_REMAIN = 1; //queue not empty
    const CLI_STATUS_PROCESSED = 2; //(some) queue items where processed
    const CLI_STATUS_ABORTED = 4; //instance didn't finish
    const CLI_STATUS_POLLABLE_PROCESSED = 8;

    /**
     * Method to set the accessMode can be gui, cli or cli_im
     *
     * @return string
     */
    public function getAccessMode()
    {
        return $this->accessMode;
    }

    /**
     * @param string $accessMode
     */
    public function setAccessMode($accessMode)
    {
        $this->accessMode = $accessMode;
    }

    /**
     * Set disabled status to prevent processes from being processed
     *
     * @param  bool $disabled (optional, defaults to true)
     * @return void
     */
    public function setDisabled($disabled = true)
    {
        if ($disabled) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($this->processFilename, '');
        } else {
            if (is_file($this->processFilename)) {
                unlink($this->processFilename);
            }
        }
    }

    /**
     * Get disable status
     *
     * @return bool true if disabled
     */
    public function getDisabled()
    {
        if (is_file($this->processFilename)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $filenameWithPath
     *
     * @return void
     */
    public function setProcessFilename($filenameWithPath)
    {
        $this->processFilename = $filenameWithPath;
    }

    /**
     * @return string
     */
    public function getProcessFilename()
    {
        return $this->processFilename;
    }

    /************************************
     *
     * Getting URLs based on Page TSconfig
     *
     ************************************/

    public function __construct()
    {
        $this->db = $GLOBALS['TYPO3_DB'];
        $this->backendUser = $GLOBALS['BE_USER'];
        $this->processFilename = PATH_site . 'typo3temp/tx_crawler.proc';

        $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);
        $settings = is_array($settings) ? $settings : [];

        // read ext_em_conf_template settings and set
        $this->setExtensionSettings($settings);

        // set defaults:
        if (\TYPO3\CMS\Core\Utility\MathUtility::convertToPositiveInteger($this->extensionSettings['countInARun']) == 0) {
            $this->extensionSettings['countInARun'] = 100;
        }

        $this->extensionSettings['processLimit'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1);
    }

    /**
     * Sets the extensions settings (unserialized pendant of $TYPO3_CONF_VARS['EXT']['extConf']['crawler']).
     *
     * @param array $extensionSettings
     * @return void
     */
    public function setExtensionSettings(array $extensionSettings)
    {
        $this->extensionSettings = $extensionSettings;
    }

    /**
     * Check if the given page should be crawled
     *
     * @param array $pageRow
     * @return false|string false if the page should be crawled (not excluded), true / skipMessage if it should be skipped
     */
    public function checkIfPageShouldBeSkipped(array $pageRow)
    {
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
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('3,4', $pageRow['doktype']) || $pageRow['doktype'] >= 199) {
                $skipPage = true;
                $skipMessage = 'Because doktype is not allowed';
            }
        }

        if (!$skipPage) {
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] as $key => $doktypeList) {
                    if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($doktypeList, $pageRow['doktype'])) {
                        $skipPage = true;
                        $skipMessage = 'Doktype was excluded by "' . $key . '"';
                        break;
                    }
                }
            }
        }

        if (!$skipPage) {
            // veto hook
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'] as $key => $func) {
                    $params = [
                        'pageRow' => $pageRow
                    ];
                    // expects "false" if page is ok and "true" or a skipMessage if this page should _not_ be crawled
                    $veto = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($func, $params, $this);
                    if ($veto !== false) {
                        $skipPage = true;
                        if (is_string($veto)) {
                            $skipMessage = $veto;
                        } else {
                            $skipMessage = 'Veto from hook "' . htmlspecialchars($key) . '"';
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
     * @param array $pageRow Page record with at least dok-type and uid columns.
     * @param string $skipMessage
     * @return array
     * @see getUrlsForPageId()
     */
    public function getUrlsForPageRow(array $pageRow, &$skipMessage = '')
    {
        $message = $this->checkIfPageShouldBeSkipped($pageRow);

        if ($message === false) {
            $forceSsl = ($pageRow['url_scheme'] === 2) ? true : false;
            $res = $this->getUrlsForPageId($pageRow['uid'], $forceSsl);
            $skipMessage = '';
        } else {
            $skipMessage = $message;
            $res = [];
        }

        return $res;
    }

    /**
     * This method is used to count if there are ANY unprocessed queue entries
     * of a given page_id and the configuration which matches a given hash.
     * If there if none, we can skip an inner detail check
     *
     * @param  int $uid
     * @param  string $configurationHash
     * @return boolean
     */
    protected function noUnprocessedQueueEntriesForPageWithConfigurationHashExist($uid, $configurationHash)
    {
        $configurationHash = $this->db->fullQuoteStr($configurationHash, 'tx_crawler_queue');
        $res = $this->db->exec_SELECTquery('count(*) as anz', 'tx_crawler_queue', "page_id=" . intval($uid) . " AND configuration_hash=" . $configurationHash . " AND exec_time=0");
        $row = $this->db->sql_fetch_assoc($res);

        return ($row['anz'] == 0);
    }

    /**
     * Creates a list of URLs from input array (and submits them to queue if asked for)
     * See Web > Info module script + "indexed_search"'s crawler hook-client using this!
     *
     * @param    array        Information about URLs from pageRow to crawl.
     * @param    array        Page row
     * @param    integer        Unix time to schedule indexing to, typically time()
     * @param    integer        Number of requests per minute (creates the interleave between requests)
     * @param    boolean        If set, submits the URLs to queue
     * @param    boolean        If set (and submitcrawlUrls is false) will fill $downloadUrls with entries)
     * @param    array        Array which is passed by reference and contains the an id per url to secure we will not crawl duplicates
     * @param    array        Array which will be filled with URLS for download if flag is set.
     * @param    array        Array of processing instructions
     * @return    string        List of URLs (meant for display in backend module)
     *
     */
    public function urlListFromUrlArray(
    array $vv,
    array $pageRow,
    $scheduledTime,
    $reqMinute,
    $submitCrawlUrls,
    $downloadCrawlUrls,
    array &$duplicateTrack,
    array &$downloadUrls,
    array $incomingProcInstructions
    ) {

        // realurl support (thanks to Ingo Renner)
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl') && $vv['subCfg']['realurl']) {

            /** @var tx_realurl $urlObj */
            $urlObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_realurl');

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

            if (!$GLOBALS['TSFE']->sys_page) {
                $GLOBALS['TSFE']->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\PageRepository');
            }
            if (!$GLOBALS['TSFE']->csConvObj) {
                $GLOBALS['TSFE']->csConvObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Charset\CharsetConverter');
            }
            if (!$GLOBALS['TSFE']->tmpl->rootLine[0]['uid']) {
                $GLOBALS['TSFE']->tmpl->rootLine[0]['uid'] = $urlObj->extConf['pagePath']['rootpage_id'];
            }
        }

        if (is_array($vv['URLs'])) {
            $configurationHash = md5(serialize($vv));
            $skipInnerCheck = $this->noUnprocessedQueueEntriesForPageWithConfigurationHashExist($pageRow['uid'], $configurationHash);

            foreach ($vv['URLs'] as $urlQuery) {
                if ($this->drawURLs_PIfilter($vv['subCfg']['procInstrFilter'], $incomingProcInstructions)) {

                    // Calculate cHash:
                    if ($vv['subCfg']['cHash']) {
                        /* @var $cacheHash \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
                        $cacheHash = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\CacheHashCalculator');
                        $urlQuery .= '&cHash=' . $cacheHash->generateForParameters($urlQuery);
                    }

                    // Create key by which to determine unique-ness:
                    $uKey = $urlQuery . '|' . $vv['subCfg']['userGroups'] . '|' . $vv['subCfg']['baseUrl'] . '|' . $vv['subCfg']['procInstrFilter'];

                    // realurl support (thanks to Ingo Renner)
                    $urlQuery = 'index.php' . $urlQuery;
                    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl') && $vv['subCfg']['realurl']) {
                        $params = [
                            'LD' => [
                                'totalURL' => $urlQuery
                            ],
                            'TCEmainHook' => true
                        ];
                        $urlObj->encodeSpURL($params);
                        $urlQuery = $params['LD']['totalURL'];
                    }

                    // Scheduled time:
                    $schTime = $scheduledTime + round(count($duplicateTrack) * (60 / $reqMinute));
                    $schTime = floor($schTime / 60) * 60;

                    if (isset($duplicateTrack[$uKey])) {

                        //if the url key is registered just display it and do not resubmit is
                        $urlList = '<em><span class="typo3-dimmed">' . htmlspecialchars($urlQuery) . '</span></em><br/>';
                    } else {
                        $urlList = '[' . date('d.m.y H:i', $schTime) . '] ' . htmlspecialchars($urlQuery);
                        $this->urlList[] = '[' . date('d.m.y H:i', $schTime) . '] ' . $urlQuery;

                        $theUrl = ($vv['subCfg']['baseUrl'] ? $vv['subCfg']['baseUrl'] : \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL')) . $urlQuery;

                        // Submit for crawling!
                        if ($submitCrawlUrls) {
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
                        } elseif ($downloadCrawlUrls) {
                            $downloadUrls[$theUrl] = $theUrl;
                        }

                        $urlList .= '<br />';
                    }
                    $duplicateTrack[$uKey] = true;
                }
            }
        } else {
            $urlList = 'ERROR - no URL generated';
        }

        return $urlList;
    }

    /**
     * Returns true if input processing instruction is among registered ones.
     *
     * @param string $piString PI to test
     * @param array $incomingProcInstructions Processing instructions
     * @return boolean
     */
    public function drawURLs_PIfilter($piString, array $incomingProcInstructions)
    {
        if (empty($incomingProcInstructions)) {
            return true;
        }

        foreach ($incomingProcInstructions as $pi) {
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($piString, $pi)) {
                return true;
            }
        }
    }

    public function getPageTSconfigForId($id)
    {
        if (!$this->MP) {
            $pageTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($id);
        } else {
            list(, $mountPointId) = explode('-', $this->MP);
            $pageTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($mountPointId);
        }

        // Call a hook to alter configuration
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'])) {
            $params = [
                'pageId' => $id,
                'pageTSConfig' => &$pageTSconfig
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'] as $userFunc) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunc, $params, $this);
            }
        }

        return $pageTSconfig;
    }

    /**
     * This methods returns an array of configurations.
     * And no urls!
     *
     * @param integer $id Page ID
     * @param bool $forceSsl Use https
     * @return array
     */
    protected function getUrlsForPageId($id, $forceSsl = false)
    {

        /**
         * Get configuration from tsConfig
         */

        // Get page TSconfig for page ID:
        $pageTSconfig = $this->getPageTSconfigForId($id);

        $res = [];

        if (is_array($pageTSconfig) && is_array($pageTSconfig['tx_crawler.']['crawlerCfg.'])) {
            $crawlerCfg = $pageTSconfig['tx_crawler.']['crawlerCfg.'];

            if (is_array($crawlerCfg['paramSets.'])) {
                foreach ($crawlerCfg['paramSets.'] as $key => $values) {
                    if (!is_array($values)) {

                        // Sub configuration for a single configuration string:
                        $subCfg = (array)$crawlerCfg['paramSets.'][$key . '.'];
                        $subCfg['key'] = $key;

                        if (strcmp($subCfg['procInstrFilter'], '')) {
                            $subCfg['procInstrFilter'] = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $subCfg['procInstrFilter']));
                        }
                        $pidOnlyList = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $subCfg['pidsOnly'], 1));

                        // process configuration if it is not page-specific or if the specific page is the current page:
                        if (!strcmp($subCfg['pidsOnly'], '') || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($pidOnlyList, $id)) {

                                // add trailing slash if not present
                            if (!empty($subCfg['baseUrl']) && substr($subCfg['baseUrl'], -1) != '/') {
                                $subCfg['baseUrl'] .= '/';
                            }

                            // Explode, process etc.:
                            $res[$key] = [];
                            $res[$key]['subCfg'] = $subCfg;
                            $res[$key]['paramParsed'] = $this->parseParams($values);
                            $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $id);
                            $res[$key]['origin'] = 'pagets';

                            // recognize MP value
                            if (!$this->MP) {
                                $res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], ['?id=' . $id]);
                            } else {
                                $res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], ['?id=' . $id . '&MP=' . $this->MP]);
                            }
                        }
                    }
                }
            }
        }

        /**
         * Get configuration from tx_crawler_configuration records
         */

        // get records along the rootline
        $rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id);

        foreach ($rootLine as $page) {
            $configurationRecordsForCurrentPage = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField(
                'tx_crawler_configuration',
                'pid',
                intval($page['uid']),
                \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_crawler_configuration') . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_crawler_configuration')
            );

            if (is_array($configurationRecordsForCurrentPage)) {
                foreach ($configurationRecordsForCurrentPage as $configurationRecord) {

                        // check access to the configuration record
                    if (empty($configurationRecord['begroups']) || $GLOBALS['BE_USER']->isAdmin() || $this->hasGroupAccess($GLOBALS['BE_USER']->user['usergroup_cached_list'], $configurationRecord['begroups'])) {
                        $pidOnlyList = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $configurationRecord['pidsonly'], 1));

                        // process configuration if it is not page-specific or if the specific page is the current page:
                        if (!strcmp($configurationRecord['pidsonly'], '') || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($pidOnlyList, $id)) {
                            $key = $configurationRecord['name'];

                            // don't overwrite previously defined paramSets
                            if (!isset($res[$key])) {

                                    /* @var $TSparserObject \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
                                $TSparserObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser');
                                $TSparserObject->parse($configurationRecord['processing_instruction_parameters_ts']);

                                $subCfg = [
                                    'procInstrFilter' => $configurationRecord['processing_instruction_filter'],
                                    'procInstrParams.' => $TSparserObject->setup,
                                    'baseUrl' => $this->getBaseUrlForConfigurationRecord(
                                        $configurationRecord['base_url'],
                                        $configurationRecord['sys_domain_base_url'],
                                        $forceSsl
                                    ),
                                    'realurl' => $configurationRecord['realurl'],
                                    'cHash' => $configurationRecord['chash'],
                                    'userGroups' => $configurationRecord['fegroups'],
                                    'exclude' => $configurationRecord['exclude'],
                                    'rootTemplatePid' => (int) $configurationRecord['root_template_pid'],
                                    'key' => $key,
                                ];

                                // add trailing slash if not present
                                if (!empty($subCfg['baseUrl']) && substr($subCfg['baseUrl'], -1) != '/') {
                                    $subCfg['baseUrl'] .= '/';
                                }
                                if (!in_array($id, $this->expandExcludeString($subCfg['exclude']))) {
                                    $res[$key] = [];
                                    $res[$key]['subCfg'] = $subCfg;
                                    $res[$key]['paramParsed'] = $this->parseParams($configurationRecord['configuration']);
                                    $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $id);
                                    $res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], ['?id=' . $id]);
                                    $res[$key]['origin'] = 'tx_crawler_configuration_' . $configurationRecord['uid'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['processUrls'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['processUrls'] as $func) {
                $params = [
                    'res' => &$res,
                ];
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($func, $params, $this);
            }
        }

        return $res;
    }

    /**
     * Checks if a domain record exist and returns the base-url based on the record. If not the given baseUrl string is used.
     *
     * @param string $baseUrl
     * @param integer $sysDomainUid
     * @param bool $ssl
     * @return string
     */
    protected function getBaseUrlForConfigurationRecord($baseUrl, $sysDomainUid, $ssl = false)
    {
        $sysDomainUid = intval($sysDomainUid);
        $urlScheme = ($ssl === false) ? 'http' : 'https';

        if ($sysDomainUid > 0) {
            $res = $this->db->exec_SELECTquery(
                '*',
                'sys_domain',
                'uid = ' . $sysDomainUid .
                \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('sys_domain') .
                \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_domain')
            );
            $row = $this->db->sql_fetch_assoc($res);
            if ($row['domainName'] != '') {
                return $urlScheme . '://' . $row['domainName'];
            }
        }
        return $baseUrl;
    }

    public function getConfigurationsForBranch($rootid, $depth)
    {
        $configurationsForBranch = [];

        $pageTSconfig = $this->getPageTSconfigForId($rootid);
        if (is_array($pageTSconfig) && is_array($pageTSconfig['tx_crawler.']['crawlerCfg.']) && is_array($pageTSconfig['tx_crawler.']['crawlerCfg.']['paramSets.'])) {
            $sets = $pageTSconfig['tx_crawler.']['crawlerCfg.']['paramSets.'];
            if (is_array($sets)) {
                foreach ($sets as $key => $value) {
                    if (!is_array($value)) {
                        continue;
                    }
                    $configurationsForBranch[] = substr($key, -1) == '.' ? substr($key, 0, -1) : $key;
                }
            }
        }
        $pids = [];
        $rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($rootid);
        foreach ($rootLine as $node) {
            $pids[] = $node['uid'];
        }
        /* @var \TYPO3\CMS\Backend\Tree\View\PageTreeView */
        $tree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Tree\View\PageTreeView');
        $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
        $tree->init('AND ' . $perms_clause);
        $tree->getTree($rootid, $depth, '');
        foreach ($tree->tree as $node) {
            $pids[] = $node['row']['uid'];
        }

        $res = $this->db->exec_SELECTquery(
            '*',
            'tx_crawler_configuration',
            'pid IN (' . implode(',', $pids) . ') ' .
            \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_crawler_configuration') .
            \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_crawler_configuration') . ' ' .
            \TYPO3\CMS\Backend\Utility\BackendUtility::versioningPlaceholderClause('tx_crawler_configuration') . ' '
        );

        while ($row = $this->db->sql_fetch_assoc($res)) {
            $configurationsForBranch[] = $row['name'];
        }
        $this->db->sql_free_result($res);
        return $configurationsForBranch;
    }

    /**
     * Check if a user has access to an item
     * (e.g. get the group list of the current logged in user from $GLOBALS['TSFE']->gr_list)
     *
     * @see \TYPO3\CMS\Frontend\Page\PageRepository::getMultipleGroupsWhereClause()
     * @param  string $groupList    Comma-separated list of (fe_)group UIDs from a user
     * @param  string $accessList   Comma-separated list of (fe_)group UIDs of the item to access
     * @return bool                 TRUE if at least one of the users group UIDs is in the access list or the access list is empty
     */
    public function hasGroupAccess($groupList, $accessList)
    {
        if (empty($accessList)) {
            return true;
        }
        foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $groupList) as $groupUid) {
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($accessList, $groupUid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parse GET vars of input Query into array with key=>value pairs
     *
     * @param string $inputQuery Input query string
     * @return array
     */
    public function parseParams($inputQuery)
    {
        // Extract all GET parameters into an ARRAY:
        $paramKeyValues = [];
        $GETparams = explode('&', $inputQuery);

        foreach ($GETparams as $paramAndValue) {
            list($p, $v) = explode('=', $paramAndValue, 2);
            if (strlen($p)) {
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
     *         - "[int]-[int]" = Integer range, will be expanded to all values in between, values included, starting from low to high (max. 1000). Example "1-34" or "-40--30"
     *         - "_TABLE:[TCA table name];[_PID:[optional page id, default is current page]];[_ENABLELANG:1]" = Look up of table records from PID, filtering out deleted records. Example "_TABLE:tt_content; _PID:123"
     *        _ENABLELANG:1 picks only original records without their language overlays
     *         - Default: Literal value
     *
     * @param array $paramArray Array with key (GET var name) and values (value of GET var which is configuration for expansion)
     * @param integer $pid Current page ID
     * @return array
     */
    public function expandParameters($paramArray, $pid)
    {
        global $TCA;

        // Traverse parameter names:
        foreach ($paramArray as $p => $v) {
            $v = trim($v);

            // If value is encapsulated in square brackets it means there are some ranges of values to find, otherwise the value is literal
            if (substr($v, 0, 1) === '[' && substr($v, -1) === ']') {
                // So, find the value inside brackets and reset the paramArray value as an array.
                $v = substr($v, 1, -1);
                $paramArray[$p] = [];

                // Explode parts and traverse them:
                $parts = explode('|', $v);
                foreach ($parts as $pV) {

                        // Look for integer range: (fx. 1-34 or -40--30 // reads minus 40 to minus 30)
                    if (preg_match('/^(-?[0-9]+)\s*-\s*(-?[0-9]+)$/', trim($pV), $reg)) {

                        // Swap if first is larger than last:
                        if ($reg[1] > $reg[2]) {
                            $temp = $reg[2];
                            $reg[2] = $reg[1];
                            $reg[1] = $temp;
                        }

                        // Traverse range, add values:
                        $runAwayBrake = 1000; // Limit to size of range!
                        for ($a = $reg[1]; $a <= $reg[2];$a++) {
                            $paramArray[$p][] = $a;
                            $runAwayBrake--;
                            if ($runAwayBrake <= 0) {
                                break;
                            }
                        }
                    } elseif (substr(trim($pV), 0, 7) == '_TABLE:') {

                        // Parse parameters:
                        $subparts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $pV);
                        $subpartParams = [];
                        foreach ($subparts as $spV) {
                            list($pKey, $pVal) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $spV);
                            $subpartParams[$pKey] = $pVal;
                        }

                        // Table exists:
                        if (isset($TCA[$subpartParams['_TABLE']])) {
                            $lookUpPid = isset($subpartParams['_PID']) ? intval($subpartParams['_PID']) : $pid;
                            $pidField = isset($subpartParams['_PIDFIELD']) ? trim($subpartParams['_PIDFIELD']) : 'pid';
                            $where = isset($subpartParams['_WHERE']) ? $subpartParams['_WHERE'] : '';
                            $addTable = isset($subpartParams['_ADDTABLE']) ? $subpartParams['_ADDTABLE'] : '';

                            $fieldName = $subpartParams['_FIELD'] ? $subpartParams['_FIELD'] : 'uid';
                            if ($fieldName === 'uid' || $TCA[$subpartParams['_TABLE']]['columns'][$fieldName]) {
                                $andWhereLanguage = '';
                                $transOrigPointerField = $TCA[$subpartParams['_TABLE']]['ctrl']['transOrigPointerField'];

                                if ($subpartParams['_ENABLELANG'] && $transOrigPointerField) {
                                    $andWhereLanguage = ' AND ' . $this->db->quoteStr($transOrigPointerField, $subpartParams['_TABLE']) . ' <= 0 ';
                                }

                                $where = $this->db->quoteStr($pidField, $subpartParams['_TABLE']) . '=' . intval($lookUpPid) . ' ' .
                                    $andWhereLanguage . $where;

                                $rows = $this->db->exec_SELECTgetRows(
                                    $fieldName,
                                    $subpartParams['_TABLE'] . $addTable,
                                    $where . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($subpartParams['_TABLE']),
                                    '',
                                    '',
                                    '',
                                    $fieldName
                                );

                                if (is_array($rows)) {
                                    $paramArray[$p] = array_merge($paramArray[$p], array_keys($rows));
                                }
                            }
                        }
                    } else { // Just add value:
                        $paramArray[$p][] = $pV;
                    }
                    // Hook for processing own expandParameters place holder
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'])) {
                        $_params = [
                            'pObj' => &$this,
                            'paramArray' => &$paramArray,
                            'currentKey' => $p,
                            'currentValue' => $pV,
                            'pid' => $pid
                        ];
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'] as $key => $_funcRef) {
                            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                        }
                    }
                }

                // Make unique set of values and sort array by key:
                $paramArray[$p] = array_unique($paramArray[$p]);
                ksort($paramArray);
            } else {
                // Set the literal value as only value in array:
                $paramArray[$p] = [$v];
            }
        }

        return $paramArray;
    }

    /**
     * Compiling URLs from parameter array (output of expandParameters())
     * The number of URLs will be the multiplication of the number of parameter values for each key
     *
     * @param array $paramArray Output of expandParameters(): Array with keys (GET var names) and for each an array of values
     * @param array $urls URLs accumulated in this array (for recursion)
     * @return array
     */
    public function compileUrls($paramArray, $urls = [])
    {
        if (count($paramArray) && is_array($urls)) {
            // shift first off stack:
            reset($paramArray);
            $varName = key($paramArray);
            $valueSet = array_shift($paramArray);

            // Traverse value set:
            $newUrls = [];
            foreach ($urls as $url) {
                foreach ($valueSet as $val) {
                    $newUrls[] = $url . (strcmp($val, '') ? '&' . rawurlencode($varName) . '=' . rawurlencode($val) : '');

                    if (count($newUrls) > \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->extensionSettings['maxCompileUrls'], 1, 1000000000, 10000)) {
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
     * @param integer $id Page ID for which to look up log entries.
     * @param string$filter Filter: "all" => all entries, "pending" => all that is not yet run, "finished" => all complete ones
     * @param boolean $doFlush If TRUE, then entries selected at DELETED(!) instead of selected!
     * @param boolean $doFullFlush
     * @param integer $itemsPerPage Limit the amount of entries per page default is 10
     * @return array
     */
    public function getLogEntriesForPageId($id, $filter = '', $doFlush = false, $doFullFlush = false, $itemsPerPage = 10)
    {
        // FIXME: Write Unit tests for Filters
        switch ($filter) {
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

        // FIXME: Write unit test that ensures that the right records are deleted.
        if ($doFlush) {
            $this->flushQueue(($doFullFlush ? '1=1' : ('page_id=' . intval($id))) . $addWhere);
            return [];
        } else {
            return $this->db->exec_SELECTgetRows(
                '*',
                'tx_crawler_queue',
                'page_id=' . intval($id) . $addWhere,
                '',
                'scheduled DESC',
                (intval($itemsPerPage) > 0 ? intval($itemsPerPage) : '')
            );
        }
    }

    /**
     * Return array of records from crawler queue for input set ID
     *
     * @param integer $set_id Set ID for which to look up log entries.
     * @param string $filter Filter: "all" => all entries, "pending" => all that is not yet run, "finished" => all complete ones
     * @param boolean $doFlush If TRUE, then entries selected at DELETED(!) instead of selected!
     * @param integer $itemsPerPage Limit the amount of entires per page default is 10
     * @return array
     */
    public function getLogEntriesForSetId($set_id, $filter = '', $doFlush = false, $doFullFlush = false, $itemsPerPage = 10)
    {
        // FIXME: Write Unit tests for Filters
        switch ($filter) {
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
        // FIXME: Write unit test that ensures that the right records are deleted.
        if ($doFlush) {
            $this->flushQueue($doFullFlush ? '' : ('set_id=' . intval($set_id) . $addWhere));
            return [];
        } else {
            return $this->db->exec_SELECTgetRows(
                '*',
                'tx_crawler_queue',
                'set_id=' . intval($set_id) . $addWhere,
                '',
                'scheduled DESC',
                (intval($itemsPerPage) > 0 ? intval($itemsPerPage) : '')
            );
        }
    }

    /**
     * Removes queue entires
     *
     * @param string $where SQL related filter for the entries which should be removed
     * @return void
     */
    protected function flushQueue($where = '')
    {
        $realWhere = strlen($where) > 0 ? $where : '1=1';

        if (tx_crawler_domain_events_dispatcher::getInstance()->hasObserver('queueEntryFlush')) {
            $groups = $this->db->exec_SELECTgetRows('DISTINCT set_id', 'tx_crawler_queue', $realWhere);
            foreach ($groups as $group) {
                tx_crawler_domain_events_dispatcher::getInstance()->post('queueEntryFlush', $group['set_id'], $this->db->exec_SELECTgetRows('uid, set_id', 'tx_crawler_queue', $realWhere . ' AND set_id="' . $group['set_id'] . '"'));
            }
        }

        $this->db->exec_DELETEquery('tx_crawler_queue', $realWhere);
    }

    /**
     * Adding call back entries to log (called from hooks typically, see indexed search class "class.crawler.php"
     *
     * @param integer $setId Set ID
     * @param array $params Parameters to pass to call back function
     * @param string $callBack Call back object reference, eg. 'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_crawler'
     * @param integer $page_id Page ID to attach it to
     * @param integer $schedule Time at which to activate
     * @return void
     */
    public function addQueueEntry_callBack($setId, $params, $callBack, $page_id = 0, $schedule = 0)
    {
        if (!is_array($params)) {
            $params = [];
        }
        $params['_CALLBACKOBJ'] = $callBack;

        // Compile value array:
        $fieldArray = [
            'page_id' => intval($page_id),
            'parameters' => serialize($params),
            'scheduled' => intval($schedule) ? intval($schedule) : $this->getCurrentTime(),
            'exec_time' => 0,
            'set_id' => intval($setId),
            'result_data' => '',
        ];

        $this->db->exec_INSERTquery('tx_crawler_queue', $fieldArray);
    }

    /************************************
     *
     * URL setting
     *
     ************************************/

    /**
     * Setting a URL for crawling:
     *
     * @param integer $id Page ID
     * @param string $url Complete URL
     * @param array $subCfg Sub configuration array (from TS config)
     * @param integer $tstamp Scheduled-time
     * @param string $configurationHash (optional) configuration hash
     * @param bool $skipInnerDuplicationCheck (optional) skip inner duplication check
     * @return bool
     */
    public function addUrl(
        $id,
        $url,
        array $subCfg,
        $tstamp,
        $configurationHash = '',
        $skipInnerDuplicationCheck = false
    ) {
        $urlAdded = false;

        // Creating parameters:
        $parameters = [
            'url' => $url
        ];

        // fe user group simulation:
        $uGs = implode(',', array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $subCfg['userGroups'], 1)));
        if ($uGs) {
            $parameters['feUserGroupList'] = $uGs;
        }

        // Setting processing instructions
        $parameters['procInstructions'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $subCfg['procInstrFilter']);
        if (is_array($subCfg['procInstrParams.'])) {
            $parameters['procInstrParams'] = $subCfg['procInstrParams.'];
        }

        // Possible TypoScript Template Parents
        $parameters['rootTemplatePid'] = $subCfg['rootTemplatePid'];

        // Compile value array:
        $parameters_serialized = serialize($parameters);
        $fieldArray = [
            'page_id' => intval($id),
            'parameters' => $parameters_serialized,
            'parameters_hash' => \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($parameters_serialized),
            'configuration_hash' => $configurationHash,
            'scheduled' => $tstamp,
            'exec_time' => 0,
            'set_id' => intval($this->setID),
            'result_data' => '',
            'configuration' => $subCfg['key'],
        ];

        if ($this->registerQueueEntriesInternallyOnly) {
            //the entries will only be registered and not stored to the database
            $this->queueEntries[] = $fieldArray;
        } else {
            if (!$skipInnerDuplicationCheck) {
                // check if there is already an equal entry
                $rows = $this->getDuplicateRowsIfExist($tstamp, $fieldArray);
            }

            if (count($rows) == 0) {
                $this->db->exec_INSERTquery('tx_crawler_queue', $fieldArray);
                $uid = $this->db->sql_insert_id();
                $rows[] = $uid;
                $urlAdded = true;
                tx_crawler_domain_events_dispatcher::getInstance()->post('urlAddedToQueue', $this->setID, ['uid' => $uid, 'fieldArray' => $fieldArray]);
            } else {
                tx_crawler_domain_events_dispatcher::getInstance()->post('duplicateUrlInQueue', $this->setID, ['rows' => $rows, 'fieldArray' => $fieldArray]);
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
     * @param array $fieldArray
     *
     * @return array;
     */
    protected function getDuplicateRowsIfExist($tstamp, $fieldArray)
    {
        $rows = [];

        $currentTime = $this->getCurrentTime();

        //if this entry is scheduled with "now"
        if ($tstamp <= $currentTime) {
            if ($this->extensionSettings['enableTimeslot']) {
                $timeBegin = $currentTime - 100;
                $timeEnd = $currentTime + 100;
                $where = ' ((scheduled BETWEEN ' . $timeBegin . ' AND ' . $timeEnd . ' ) OR scheduled <= ' . $currentTime . ') ';
            } else {
                $where = 'scheduled <= ' . $currentTime;
            }
        } elseif ($tstamp > $currentTime) {
            //entry with a timestamp in the future need to have the same schedule time
            $where = 'scheduled = ' . $tstamp ;
        }

        if (!empty($where)) {
            $result = $this->db->exec_SELECTgetRows(
                'qid',
                'tx_crawler_queue',
                $where .
                ' AND NOT exec_time' .
                ' AND NOT process_id ' .
                ' AND page_id=' . intval($fieldArray['page_id']) .
                ' AND parameters_hash = ' . $this->db->fullQuoteStr($fieldArray['parameters_hash'], 'tx_crawler_queue')
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
     * @return int
     */
    public function getCurrentTime()
    {
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
     * @param integer $queueId
     * @param boolean $force If set, will process even if exec_time has been set!
     * @return integer
     */
    public function readUrl($queueId, $force = false)
    {
        $ret = 0;
        if ($this->debugMode) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devlog('crawler-readurl start ' . microtime(true), __FUNCTION__);
        }
        // Get entry:
        list($queueRec) = $this->db->exec_SELECTgetRows(
            '*',
            'tx_crawler_queue',
            'qid=' . intval($queueId) . ($force ? '' : ' AND exec_time=0 AND process_scheduled > 0')
        );

        if (!is_array($queueRec)) {
            return;
        }

        $parameters = unserialize($queueRec['parameters']);
        if ($parameters['rootTemplatePid']) {
            $this->initTSFE((int)$parameters['rootTemplatePid']);
        } else {
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
                'Page with (' . $queueRec['page_id'] . ') could not be crawled, please check your crawler configuration. Perhaps no Root Template Pid is set',
                'crawler',
                \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING
            );
        }

        \AOE\Crawler\Utility\SignalSlotUtility::emitSignal(
            __CLASS__,
            \AOE\Crawler\Utility\SignalSlotUtility::SIGNNAL_QUEUEITEM_PREPROCESS,
            [$queueId, &$queueRec]
        );

        // Set exec_time to lock record:
        $field_array = ['exec_time' => $this->getCurrentTime()];

        if (isset($this->processID)) {
            //if mulitprocessing is used we need to store the id of the process which has handled this entry
            $field_array['process_id_completed'] = $this->processID;
        }
        $this->db->exec_UPDATEquery('tx_crawler_queue', 'qid=' . intval($queueId), $field_array);

        $result = $this->readUrl_exec($queueRec);
        $resultData = unserialize($result['content']);

        //atm there's no need to point to specific pollable extensions
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'] as $pollable) {
                // only check the success value if the instruction is runnig
                // it is important to name the pollSuccess key same as the procInstructions key
                if (is_array($resultData['parameters']['procInstructions']) && in_array(
                    $pollable,
                        $resultData['parameters']['procInstructions']
                )
                ) {
                    if (!empty($resultData['success'][$pollable]) && $resultData['success'][$pollable]) {
                        $ret |= self::CLI_STATUS_POLLABLE_PROCESSED;
                    }
                }
            }
        }

        // Set result in log which also denotes the end of the processing of this entry.
        $field_array = ['result_data' => serialize($result)];

        \AOE\Crawler\Utility\SignalSlotUtility::emitSignal(
            __CLASS__,
            \AOE\Crawler\Utility\SignalSlotUtility::SIGNNAL_QUEUEITEM_POSTPROCESS,
            [$queueId, &$field_array]
        );

        $this->db->exec_UPDATEquery('tx_crawler_queue', 'qid=' . intval($queueId), $field_array);

        if ($this->debugMode) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devlog('crawler-readurl stop ' . microtime(true), __FUNCTION__);
        }

        return $ret;
    }

    /**
     * Read URL for not-yet-inserted log-entry
     *
     * @param integer $field_array Queue field array,
     * @return string
     */
    public function readUrlFromArray($field_array)
    {

            // Set exec_time to lock record:
        $field_array['exec_time'] = $this->getCurrentTime();
        $this->db->exec_INSERTquery('tx_crawler_queue', $field_array);
        $queueId = $field_array['qid'] = $this->db->sql_insert_id();

        $result = $this->readUrl_exec($field_array);

        // Set result in log which also denotes the end of the processing of this entry.
        $field_array = ['result_data' => serialize($result)];

        \AOE\Crawler\Utility\SignalSlotUtility::emitSignal(
            __CLASS__,
            \AOE\Crawler\Utility\SignalSlotUtility::SIGNNAL_QUEUEITEM_POSTPROCESS,
            [$queueId, &$field_array]
        );

        $this->db->exec_UPDATEquery('tx_crawler_queue', 'qid=' . intval($queueId), $field_array);

        return $result;
    }

    /**
     * Read URL for a queue record
     *
     * @param array $queueRec Queue record
     * @return string
     */
    public function readUrl_exec($queueRec)
    {
        // Decode parameters:
        $parameters = unserialize($queueRec['parameters']);
        $result = 'ERROR';
        if (is_array($parameters)) {
            if ($parameters['_CALLBACKOBJ']) { // Calling object:
                $objRef = $parameters['_CALLBACKOBJ'];
                $callBackObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef);
                if (is_object($callBackObj)) {
                    unset($parameters['_CALLBACKOBJ']);
                    $result = ['content' => serialize($callBackObj->crawler_execute($parameters, $this))];
                } else {
                    $result = ['content' => 'No object: ' . $objRef];
                }
            } else { // Regular FE request:

                // Prepare:
                $crawlerId = $queueRec['qid'] . ':' . md5($queueRec['qid'] . '|' . $queueRec['set_id'] . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);

                // Get result:
                $result = $this->requestUrl($parameters['url'], $crawlerId);

                tx_crawler_domain_events_dispatcher::getInstance()->post('urlCrawled', $queueRec['set_id'], ['url' => $parameters['url'], 'result' => $result]);
            }
        }

        return $result;
    }

    /**
     * Gets the content of a URL.
     *
     * @param string $originalUrl URL to read
     * @param string $crawlerId Crawler ID string (qid + hash to verify)
     * @param integer $timeout Timeout time
     * @param integer $recursion Recursion limiter for 302 redirects
     * @return array
     */
    public function requestUrl($originalUrl, $crawlerId, $timeout = 2, $recursion = 10)
    {
        if (!$recursion) {
            return false;
        }

        // Parse URL, checking for scheme:
        $url = parse_url($originalUrl);

        if ($url === false) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(sprintf('Could not parse_url() for string "%s"', $url), 'crawler', 4, ['crawlerId' => $crawlerId]);
            }
            return false;
        }

        if (!in_array($url['scheme'], ['','http','https'])) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(sprintf('Scheme does not match for url "%s"', $url), 'crawler', 4, ['crawlerId' => $crawlerId]);
            }
            return false;
        }

        // direct request
        if ($this->extensionSettings['makeDirectRequests']) {
            $result = $this->sendDirectRequest($originalUrl, $crawlerId);
            return $result;
        }

        $reqHeaders = $this->buildRequestHeaderArray($url, $crawlerId);

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

        if (!$fp) {
            if (TYPO3_DLOG) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(sprintf('Error while opening "%s"', $url), 'crawler', 4, ['crawlerId' => $crawlerId]);
            }
            return false;
        } else {
            // Request message:
            $msg = implode("\r\n", $reqHeaders) . "\r\n\r\n";
            fputs($fp, $msg);

            // Read response:
            $d = $this->getHttpResponseFromStream($fp);
            fclose($fp);

            $time = microtime(true) - $startTime;
            $this->log($originalUrl . ' ' . $time);

            // Implode content and headers:
            $result = [
                'request' => $msg,
                'headers' => implode('', $d['headers']),
                'content' => implode('', (array)$d['content'])
            ];

            if (($this->extensionSettings['follow30x']) && ($newUrl = $this->getRequestUrlFrom302Header($d['headers'], $url['user'], $url['pass']))) {
                $result = array_merge(['parentRequest' => $result], $this->requestUrl($newUrl, $crawlerId, $recursion--));
                $newRequestUrl = $this->requestUrl($newUrl, $crawlerId, $timeout, --$recursion);

                if (is_array($newRequestUrl)) {
                    $result = array_merge(['parentRequest' => $result], $newRequestUrl);
                } else {
                    if (TYPO3_DLOG) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(sprintf('Error while opening "%s"', $url), 'crawler', 4, ['crawlerId' => $crawlerId]);
                    }
                    return false;
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
    protected function getFrontendBasePath()
    {
        $frontendBasePath = '/';

        // Get the path from the extension settings:
        if (isset($this->extensionSettings['frontendBasePath']) && $this->extensionSettings['frontendBasePath']) {
            $frontendBasePath = $this->extensionSettings['frontendBasePath'];
            // If empty, try to use config.absRefPrefix:
        } elseif (isset($GLOBALS['TSFE']->absRefPrefix) && !empty($GLOBALS['TSFE']->absRefPrefix)) {
            $frontendBasePath = $GLOBALS['TSFE']->absRefPrefix;
            // If not in CLI mode the base path can be determined from $_SERVER environment:
        } elseif (!defined('TYPO3_REQUESTTYPE_CLI') || !TYPO3_REQUESTTYPE_CLI) {
            $frontendBasePath = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
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
    protected function executeShellCommand($command)
    {
        $result = shell_exec($command);
        return $result;
    }

    /**
     * Reads HTTP response from the given stream.
     *
     * @param  resource $streamPointer  Pointer to connection stream.
     * @return array                    Associative array with the following items:
     *                                  headers <array> Response headers sent by server.
     *                                  content <array> Content, with each line as an array item.
     */
    protected function getHttpResponseFromStream($streamPointer)
    {
        $response = ['headers' => [], 'content' => []];

        if (is_resource($streamPointer)) {
            // read headers
            while ($line = fgets($streamPointer, '2048')) {
                $line = trim($line);
                if ($line !== '') {
                    $response['headers'][] = $line;
                } else {
                    break;
                }
            }

            // read content
            while ($line = fgets($streamPointer, '2048')) {
                $response['content'][] = $line;
            }
        }

        return $response;
    }

    /**
     * @param message
     */
    protected function log($message)
    {
        if (!empty($this->extensionSettings['logFileName'])) {
            @file_put_contents($this->extensionSettings['logFileName'], date('Ymd His') . ' ' . $message . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Builds HTTP request headers.
     *
     * @param array $url
     * @param string $crawlerId
     *
     * @return array
     */
    protected function buildRequestHeaderArray(array $url, $crawlerId)
    {
        $reqHeaders = [];
        $reqHeaders[] = 'GET ' . $url['path'] . ($url['query'] ? '?' . $url['query'] : '') . ' HTTP/1.0';
        $reqHeaders[] = 'Host: ' . $url['host'];
        if (stristr($url['query'], 'ADMCMD_previewWS')) {
            $reqHeaders[] = 'Cookie: $Version="1"; be_typo_user="1"; $Path=/';
        }
        $reqHeaders[] = 'Connection: close';
        if ($url['user'] != '') {
            $reqHeaders[] = 'Authorization: Basic ' . base64_encode($url['user'] . ':' . $url['pass']);
        }
        $reqHeaders[] = 'X-T3crawler: ' . $crawlerId;
        $reqHeaders[] = 'User-Agent: TYPO3 crawler';
        return $reqHeaders;
    }

    /**
     * Check if the submitted HTTP-Header contains a redirect location and built new crawler-url
     *
     * @param array $headers HTTP Header
     * @param string $user HTTP Auth. User
     * @param string $pass HTTP Auth. Password
     * @return string
     */
    protected function getRequestUrlFrom302Header($headers, $user = '', $pass = '')
    {
        if (!is_array($headers)) {
            return false;
        }
        if (!(stristr($headers[0], '301 Moved') || stristr($headers[0], '302 Found') || stristr($headers[0], '302 Moved'))) {
            return false;
        }

        foreach ($headers as $hl) {
            $tmp = explode(": ", $hl);
            $header[trim($tmp[0])] = trim($tmp[1]);
            if (trim($tmp[0]) == 'Location') {
                break;
            }
        }
        if (!array_key_exists('Location', $header)) {
            return false;
        }

        if ($user != '') {
            if (!($tmp = parse_url($header['Location']))) {
                return false;
            }
            $newUrl = $tmp['scheme'] . '://' . $user . ':' . $pass . '@' . $tmp['host'] . $tmp['path'];
            if ($tmp['query'] != '') {
                $newUrl .= '?' . $tmp['query'];
            }
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
     * @param array $params Parameters from frontend
     * @param object $ref TSFE object (reference under PHP5)
     * @return void
     */
    public function fe_init(&$params, $ref)
    {

            // Authenticate crawler request:
        if (isset($_SERVER['HTTP_X_T3CRAWLER'])) {
            list($queueId, $hash) = explode(':', $_SERVER['HTTP_X_T3CRAWLER']);
            list($queueRec) = $this->db->exec_SELECTgetRows('*', 'tx_crawler_queue', 'qid=' . intval($queueId));

            // If a crawler record was found and hash was matching, set it up:
            if (is_array($queueRec) && $hash === md5($queueRec['qid'] . '|' . $queueRec['set_id'] . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
                $params['pObj']->applicationData['tx_crawler']['running'] = true;
                $params['pObj']->applicationData['tx_crawler']['parameters'] = unserialize($queueRec['parameters']);
                $params['pObj']->applicationData['tx_crawler']['log'] = [];
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
     * @param integer $id Root page id to start from.
     * @param integer $depth Depth of tree, 0=only id-page, 1= on sublevel, 99 = infinite
     * @param integer $scheduledTime Unix Time when the URL is timed to be visited when put in queue
     * @param integer $reqMinute Number of requests per minute (creates the interleave between requests)
     * @param boolean $submitCrawlUrls If set, submits the URLs to queue in database (real crawling)
     * @param boolean $downloadCrawlUrls If set (and submitcrawlUrls is false) will fill $downloadUrls with entries)
     * @param array $incomingProcInstructions Array of processing instructions
     * @param array $configurationSelection Array of configuration keys
     * @return string
     */
    public function getPageTreeAndUrls(
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
            $LANG = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('language');
            $LANG->init(0);
        }
        $this->scheduledTime = $scheduledTime;
        $this->reqMinute = $reqMinute;
        $this->submitCrawlUrls = $submitCrawlUrls;
        $this->downloadCrawlUrls = $downloadCrawlUrls;
        $this->incomingProcInstructions = $incomingProcInstructions;
        $this->incomingConfigurationSelection = $configurationSelection;

        $this->duplicateTrack = [];
        $this->downloadUrls = [];

        // Drawing tree:
        /* @var $tree \TYPO3\CMS\Backend\Tree\View\PageTreeView */
        $tree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Tree\View\PageTreeView');
        $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
        $tree->init('AND ' . $perms_clause);

        $pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($id, $perms_clause);

        // Set root row:
        $tree->tree[] = [
            'row' => $pageinfo,
            'HTML' => \AOE\Crawler\Utility\IconUtility::getIconForRecord('pages', $pageinfo)
        ];

        // Get branch beneath:
        if ($depth) {
            $tree->getTree($id, $depth, '');
        }

        // Traverse page tree:
        $code = '';

        foreach ($tree->tree as $data) {
            $this->MP = false;

            // recognize mount points
            if ($data['row']['doktype'] == 7) {
                $mountpage = $this->db->exec_SELECTgetRows('*', 'pages', 'uid = ' . $data['row']['uid']);

                // fetch mounted pages
                $this->MP = $mountpage[0]['mount_pid'] . '-' . $data['row']['uid'];

                $mountTree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Tree\View\PageTreeView');
                $mountTree->init('AND ' . $perms_clause);
                $mountTree->getTree($mountpage[0]['mount_pid'], $depth, '');

                foreach ($mountTree->tree as $mountData) {
                    $code .= $this->drawURLs_addRowsForPage(
                        $mountData['row'],
                        $mountData['HTML'] . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $mountData['row'], true)
                    );
                }

                // replace page when mount_pid_ol is enabled
                if ($mountpage[0]['mount_pid_ol']) {
                    $data['row']['uid'] = $mountpage[0]['mount_pid'];
                } else {
                    // if the mount_pid_ol is not set the MP must not be used for the mountpoint page
                    $this->MP = false;
                }
            }

            $code .= $this->drawURLs_addRowsForPage(
                $data['row'],
                $data['HTML'] . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $data['row'], true)
            );
        }

        return $code;
    }

    /**
     * Expands exclude string
     *
     * @param string $excludeString Exclude string
     * @return array
     */
    public function expandExcludeString($excludeString)
    {
        // internal static caches;
        static $expandedExcludeStringCache;
        static $treeCache;

        if (empty($expandedExcludeStringCache[$excludeString])) {
            $pidList = [];

            if (!empty($excludeString)) {
                /* @var $tree \TYPO3\CMS\Backend\Tree\View\PageTreeView */
                $tree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Tree\View\PageTreeView');
                $tree->init('AND ' . $this->backendUser->getPagePermsClause(1));

                $excludeParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $excludeString);

                foreach ($excludeParts as $excludePart) {
                    list($pid, $depth) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('+', $excludePart);

                    // default is "page only" = "depth=0"
                    if (empty($depth)) {
                        $depth = (stristr($excludePart, '+')) ? 99 : 0;
                    }

                    $pidList[] = $pid;

                    if ($depth > 0) {
                        if (empty($treeCache[$pid][$depth])) {
                            $tree->reset();
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
     * @param    array        Page row
     * @param    string        Page icon and title for row
     * @return    string        HTML <tr> content (one or more)
     */
    public function drawURLs_addRowsForPage(array $pageRow, $pageTitleAndIcon)
    {
        $skipMessage = '';

        // Get list of configurations
        $configurations = $this->getUrlsForPageRow($pageRow, $skipMessage);

        if (count($this->incomingConfigurationSelection) > 0) {
            // remove configuration that does not match the current selection
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
            foreach ($configurations as $confKey => $confArray) {

                    // Title column:
                if (!$c) {
                    $titleClm = '<td rowspan="' . count($configurations) . '">' . $pageTitleAndIcon . '</td>';
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
                    $calcAccu = [];
                    $calcRes = 1;
                    foreach ($confArray['paramExpanded'] as $gVar => $gVal) {
                        $paramExpanded .= '
                            <tr>
                                <td class="bgColor4-20">' . htmlspecialchars('&' . $gVar . '=') . '<br/>' .
                                                '(' . count($gVal) . ')' .
                                                '</td>
                                <td class="bgColor4" nowrap="nowrap">' . nl2br(htmlspecialchars(implode(chr(10), $gVal))) . '</td>
                            </tr>
                        ';
                        $calcRes *= count($gVal);
                        $calcAccu[] = count($gVal);
                    }
                    $paramExpanded = '<table class="lrPadding c-list param-expanded">' . $paramExpanded . '</table>';
                    $paramExpanded .= 'Comb: ' . implode('*', $calcAccu) . '=' . $calcRes;

                    // Options
                    $optionValues = '';
                    if ($confArray['subCfg']['userGroups']) {
                        $optionValues .= 'User Groups: ' . $confArray['subCfg']['userGroups'] . '<br/>';
                    }
                    if ($confArray['subCfg']['baseUrl']) {
                        $optionValues .= 'Base Url: ' . $confArray['subCfg']['baseUrl'] . '<br/>';
                    }
                    if ($confArray['subCfg']['procInstrFilter']) {
                        $optionValues .= 'ProcInstr: ' . $confArray['subCfg']['procInstrFilter'] . '<br/>';
                    }

                    // Compile row:
                    $content .= '
                        <tr class="bgColor' . ($c % 2 ? '-20' : '-10') . '">
                            ' . $titleClm . '
                            <td>' . htmlspecialchars($confKey) . '</td>
                            <td>' . nl2br(htmlspecialchars(rawurldecode(trim(str_replace('&', chr(10) . '&', \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $confArray['paramParsed'])))))) . '</td>
                            <td>' . $paramExpanded . '</td>
                            <td nowrap="nowrap">' . $urlList . '</td>
                            <td nowrap="nowrap">' . $optionValues . '</td>
                            <td nowrap="nowrap">' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($confArray['subCfg']['procInstrParams.']) . '</td>
                        </tr>';
                } else {
                    $content .= '<tr class="bgColor' . ($c % 2 ? '-20' : '-10') . '">
                            ' . $titleClm . '
                            <td>' . htmlspecialchars($confKey) . '</td>
                            <td colspan="5"><em>No entries</em> (Page is excluded in this configuration)</td>
                        </tr>';
                }

                $c++;
            }
        } else {
            $message = !empty($skipMessage) ? ' (' . $skipMessage . ')' : '';

            // Compile row:
            $content .= '
                <tr class="bgColor-20" style="border-bottom: 1px solid black;">
                    <td>' . $pageTitleAndIcon . '</td>
                    <td colspan="6"><em>No entries</em>' . $message . '</td>
                </tr>';
        }

        return $content;
    }

    /**
     * @return int
     */
    public function getUnprocessedItemsCount()
    {
        $res = $this->db->exec_SELECTquery(
            'count(*) as num',
            'tx_crawler_queue',
            'exec_time=0 AND process_scheduled=0 AND scheduled<=' . $this->getCurrentTime()
        );

        $count = $this->db->sql_fetch_assoc($res);
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
     * @return int number of remaining items or false if error
     */
    public function CLI_main()
    {
        $this->setAccessMode('cli');
        $result = self::CLI_STATUS_NOTHING_PROCCESSED;
        $cliObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_crawler_cli');

        if (isset($cliObj->cli_args['-h']) || isset($cliObj->cli_args['--help'])) {
            $cliObj->cli_validateArgs();
            $cliObj->cli_help();
            exit;
        }

        if (!$this->getDisabled() && $this->CLI_checkAndAcquireNewProcess($this->CLI_buildProcessId())) {
            $countInARun = $cliObj->cli_argValue('--countInARun') ? intval($cliObj->cli_argValue('--countInARun')) : $this->extensionSettings['countInARun'];
            // Seconds
            $sleepAfterFinish = $cliObj->cli_argValue('--sleepAfterFinish') ? intval($cliObj->cli_argValue('--sleepAfterFinish')) : $this->extensionSettings['sleepAfterFinish'];
            // Milliseconds
            $sleepTime = $cliObj->cli_argValue('--sleepTime') ? intval($cliObj->cli_argValue('--sleepTime')) : $this->extensionSettings['sleepTime'];

            try {
                // Run process:
                $result = $this->CLI_run($countInARun, $sleepTime, $sleepAfterFinish);
            } catch (Exception $e) {
                $this->CLI_debug(get_class($e) . ': ' . $e->getMessage());
                $result = self::CLI_STATUS_ABORTED;
            }

            // Cleanup
            $this->db->exec_DELETEquery('tx_crawler_process', 'assigned_items_count = 0');

            //TODO can't we do that in a clean way?
            $releaseStatus = $this->CLI_releaseProcesses($this->CLI_buildProcessId());

            $this->CLI_debug("Unprocessed Items remaining:" . $this->getUnprocessedItemsCount() . " (" . $this->CLI_buildProcessId() . ")");
            $result |= ($this->getUnprocessedItemsCount() > 0 ? self::CLI_STATUS_REMAIN : self::CLI_STATUS_NOTHING_PROCCESSED);
        } else {
            $result |= self::CLI_STATUS_ABORTED;
        }

        return $result;
    }

    /**
     * Function executed by crawler_im.php cli script.
     *
     * @return void
     */
    public function CLI_main_im()
    {
        $this->setAccessMode('cli_im');

        $cliObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_crawler_cli_im');

        // Force user to admin state and set workspace to "Live":
        $this->backendUser->user['admin'] = 1;
        $this->backendUser->setWorkspace(0);

        // Print help
        if (!isset($cliObj->cli_args['_DEFAULT'][1])) {
            $cliObj->cli_validateArgs();
            $cliObj->cli_help();
            exit;
        }

        $cliObj->cli_validateArgs();

        if ($cliObj->cli_argValue('-o') === 'exec') {
            $this->registerQueueEntriesInternallyOnly = true;
        }

        if (isset($cliObj->cli_args['_DEFAULT'][2])) {
            // Crawler is called over TYPO3 BE
            $pageId = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cliObj->cli_args['_DEFAULT'][2], 0);
        } else {
            // Crawler is called over cli
            $pageId = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cliObj->cli_args['_DEFAULT'][1], 0);
        }

        $configurationKeys = $this->getConfigurationKeys($cliObj);

        if (!is_array($configurationKeys)) {
            $configurations = $this->getUrlsForPageId($pageId);
            if (is_array($configurations)) {
                $configurationKeys = array_keys($configurations);
            } else {
                $configurationKeys = [];
            }
        }

        if ($cliObj->cli_argValue('-o') === 'queue' || $cliObj->cli_argValue('-o') === 'exec') {
            $reason = new tx_crawler_domain_reason();
            $reason->setReason(tx_crawler_domain_reason::REASON_GUI_SUBMIT);
            $reason->setDetailText('The cli script of the crawler added to the queue');
            tx_crawler_domain_events_dispatcher::getInstance()->post(
                'invokeQueueChange',
                $this->setID,
                ['reason' => $reason]
            );
        }

        if ($this->extensionSettings['cleanUpOldQueueEntries']) {
            $this->cleanUpOldQueueEntries();
        }

        $this->setID = \TYPO3\CMS\Core\Utility\GeneralUtility::md5int(microtime());
        $this->getPageTreeAndUrls(
            $pageId,
            \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cliObj->cli_argValue('-d'), 0, 99),
            $this->getCurrentTime(),
            \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cliObj->cli_isArg('-n') ? $cliObj->cli_argValue('-n') : 30, 1, 1000),
            $cliObj->cli_argValue('-o') === 'queue' || $cliObj->cli_argValue('-o') === 'exec',
            $cliObj->cli_argValue('-o') === 'url',
            \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $cliObj->cli_argValue('-proc'), 1),
            $configurationKeys
        );

        if ($cliObj->cli_argValue('-o') === 'url') {
            $cliObj->cli_echo(implode(chr(10), $this->downloadUrls) . chr(10), 1);
        } elseif ($cliObj->cli_argValue('-o') === 'exec') {
            $cliObj->cli_echo("Executing " . count($this->urlList) . " requests right away:\n\n");
            $cliObj->cli_echo(implode(chr(10), $this->urlList) . chr(10));
            $cliObj->cli_echo("\nProcessing:\n");

            foreach ($this->queueEntries as $queueRec) {
                $p = unserialize($queueRec['parameters']);
                $cliObj->cli_echo($p['url'] . ' (' . implode(',', $p['procInstructions']) . ') => ');

                $result = $this->readUrlFromArray($queueRec);

                $requestResult = unserialize($result['content']);
                if (is_array($requestResult)) {
                    $resLog = is_array($requestResult['log']) ? chr(10) . chr(9) . chr(9) . implode(chr(10) . chr(9) . chr(9), $requestResult['log']) : '';
                    $cliObj->cli_echo('OK: ' . $resLog . chr(10));
                } else {
                    $cliObj->cli_echo('Error checking Crawler Result: ' . substr(preg_replace('/\s+/', ' ', strip_tags($result['content'])), 0, 30000) . '...' . chr(10));
                }
            }
        } elseif ($cliObj->cli_argValue('-o') === 'queue') {
            $cliObj->cli_echo("Putting " . count($this->urlList) . " entries in queue:\n\n");
            $cliObj->cli_echo(implode(chr(10), $this->urlList) . chr(10));
        } else {
            $cliObj->cli_echo(count($this->urlList) . " entries found for processing. (Use -o to decide action):\n\n", 1);
            $cliObj->cli_echo(implode(chr(10), $this->urlList) . chr(10), 1);
        }
    }

    /**
     * Function executed by crawler_im.php cli script.
     *
     * @return bool
     */
    public function CLI_main_flush()
    {
        $this->setAccessMode('cli_flush');
        $cliObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_crawler_cli_flush');

        // Force user to admin state and set workspace to "Live":
        $this->backendUser->user['admin'] = 1;
        $this->backendUser->setWorkspace(0);

        // Print help
        if (!isset($cliObj->cli_args['_DEFAULT'][1])) {
            $cliObj->cli_validateArgs();
            $cliObj->cli_help();
            exit;
        }

        $cliObj->cli_validateArgs();
        $pageId = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cliObj->cli_args['_DEFAULT'][1], 0);
        $fullFlush = ($pageId == 0);

        $mode = $cliObj->cli_argValue('-o');

        switch ($mode) {
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

        return $result !== false;
    }

    /**
     * Obtains configuration keys from the CLI arguments
     *
     * @param  tx_crawler_cli_im $cliObj    Command line object
     * @return mixed                        Array of keys or null if no keys found
     */
    protected function getConfigurationKeys(tx_crawler_cli_im &$cliObj)
    {
        $parameter = trim($cliObj->cli_argValue('-conf'));
        return ($parameter != '' ? \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $parameter) : []);
    }

    /**
     * Running the functionality of the CLI (crawling URLs from queue)
     *
     * @param int $countInARun
     * @param int $sleepTime
     * @param int $sleepAfterFinish
     * @return string
     */
    public function CLI_run($countInARun, $sleepTime, $sleepAfterFinish)
    {
        $result = 0;
        $counter = 0;

        // First, run hooks:
        $this->CLI_runHooks();

        // Clean up the queue
        if (intval($this->extensionSettings['purgeQueueDays']) > 0) {
            $purgeDate = $this->getCurrentTime() - 24 * 60 * 60 * intval($this->extensionSettings['purgeQueueDays']);
            $del = $this->db->exec_DELETEquery(
                'tx_crawler_queue',
                'exec_time!=0 AND exec_time<' . $purgeDate
            );
        }

        // Select entries:
        //TODO Shouldn't this reside within the transaction?
        $rows = $this->db->exec_SELECTgetRows(
            'qid,scheduled',
            'tx_crawler_queue',
            'exec_time=0
                AND process_scheduled= 0
                AND scheduled<=' . $this->getCurrentTime(),
            '',
            'scheduled, qid',
        intval($countInARun)
        );

        if (count($rows) > 0) {
            $quidList = [];

            foreach ($rows as $r) {
                $quidList[] = $r['qid'];
            }

            $processId = $this->CLI_buildProcessId();

            //reserve queue entrys for process
            $this->db->sql_query('BEGIN');
            //TODO make sure we're not taking assigned queue-entires
            $this->db->exec_UPDATEquery(
                'tx_crawler_queue',
                'qid IN (' . implode(',', $quidList) . ')',
                [
                    'process_scheduled' => intval($this->getCurrentTime()),
                    'process_id' => $processId
                ]
            );

            //save the number of assigned queue entrys to determine who many have been processed later
            $numberOfAffectedRows = $this->db->sql_affected_rows();
            $this->db->exec_UPDATEquery(
                'tx_crawler_process',
                "process_id = '" . $processId . "'",
                [
                    'assigned_items_count' => intval($numberOfAffectedRows)
                ]
            );

            if ($numberOfAffectedRows == count($quidList)) {
                $this->db->sql_query('COMMIT');
            } else {
                $this->db->sql_query('ROLLBACK');
                $this->CLI_debug("Nothing processed due to multi-process collision (" . $this->CLI_buildProcessId() . ")");
                return ($result | self::CLI_STATUS_ABORTED);
            }

            foreach ($rows as $r) {
                $result |= $this->readUrl($r['qid']);

                $counter++;
                usleep(intval($sleepTime)); // Just to relax the system

                // if during the start and the current read url the cli has been disable we need to return from the function
                // mark the process NOT as ended.
                if ($this->getDisabled()) {
                    return ($result | self::CLI_STATUS_ABORTED);
                }

                if (!$this->CLI_checkIfProcessIsActive($this->CLI_buildProcessId())) {
                    $this->CLI_debug("conflict / timeout (" . $this->CLI_buildProcessId() . ")");

                    //TODO might need an additional returncode
                    $result |= self::CLI_STATUS_ABORTED;
                    break; //possible timeout
                }
            }

            sleep(intval($sleepAfterFinish));

            $msg = 'Rows: ' . $counter;
            $this->CLI_debug($msg . " (" . $this->CLI_buildProcessId() . ")");
        } else {
            $this->CLI_debug("Nothing within queue which needs to be processed (" . $this->CLI_buildProcessId() . ")");
        }

        if ($counter > 0) {
            $result |= self::CLI_STATUS_PROCESSED;
        }

        return $result;
    }

    /**
     * Activate hooks
     *
     * @return void
     */
    public function CLI_runHooks()
    {
        global $TYPO3_CONF_VARS;
        if (is_array($TYPO3_CONF_VARS['EXTCONF']['crawler']['cli_hooks'])) {
            foreach ($TYPO3_CONF_VARS['EXTCONF']['crawler']['cli_hooks'] as $objRef) {
                $hookObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($objRef);
                if (is_object($hookObj)) {
                    $hookObj->crawler_init($this);
                }
            }
        }
    }

    /**
     * Try to acquire a new process with the given id
     * also performs some auto-cleanup for orphan processes
     * @todo preemption might not be the most elegant way to clean up
     *
     * @param string $id identification string for the process
     * @return boolean
     */
    public function CLI_checkAndAcquireNewProcess($id)
    {
        $ret = true;

        $systemProcessId = getmypid();
        if ($systemProcessId < 1) {
            return false;
        }

        $processCount = 0;
        $orphanProcesses = [];

        $this->db->sql_query('BEGIN');

        $res = $this->db->exec_SELECTquery(
            'process_id,ttl',
            'tx_crawler_process',
            'active=1 AND deleted=0'
            );

        $currentTime = $this->getCurrentTime();

        while ($row = $this->db->sql_fetch_assoc($res)) {
            if ($row['ttl'] < $currentTime) {
                $orphanProcesses[] = $row['process_id'];
            } else {
                $processCount++;
            }
        }

        // if there are less than allowed active processes then add a new one
        if ($processCount < intval($this->extensionSettings['processLimit'])) {
            $this->CLI_debug("add process " . $this->CLI_buildProcessId() . " (" . ($processCount + 1) . "/" . intval($this->extensionSettings['processLimit']) . ")");

            // create new process record
            $this->db->exec_INSERTquery(
                'tx_crawler_process',
                [
                    'process_id' => $id,
                    'active' => '1',
                    'ttl' => ($currentTime + intval($this->extensionSettings['processMaxRunTime'])),
                    'system_process_id' => $systemProcessId
                ]
                );
        } else {
            $this->CLI_debug("Processlimit reached (" . ($processCount) . "/" . intval($this->extensionSettings['processLimit']) . ")");
            $ret = false;
        }

        $this->CLI_releaseProcesses($orphanProcesses, true); // maybe this should be somehow included into the current lock
        $this->CLI_deleteProcessesMarkedDeleted();

        $this->db->sql_query('COMMIT');

        return $ret;
    }

    /**
     * Release a process and the required resources
     *
     * @param  mixed    $releaseIds   string with a single process-id or array with multiple process-ids
     * @param  boolean  $withinLock   show whether the DB-actions are included within an existing lock
     * @return boolean
     */
    public function CLI_releaseProcesses($releaseIds, $withinLock = false)
    {
        if (!is_array($releaseIds)) {
            $releaseIds = [$releaseIds];
        }

        if (!count($releaseIds) > 0) {
            return false;   //nothing to release
        }

        if (!$withinLock) {
            $this->db->sql_query('BEGIN');
        }

        // some kind of 2nd chance algo - this way you need at least 2 processes to have a real cleanup
        // this ensures that a single process can't mess up the entire process table

        // mark all processes as deleted which have no "waiting" queue-entires and which are not active
        $this->db->exec_UPDATEquery(
            'tx_crawler_queue',
            'process_id IN (SELECT process_id FROM tx_crawler_process WHERE active=0 AND deleted=0)',
            [
                'process_scheduled' => 0,
                'process_id' => ''
            ]
        );
        $this->db->exec_UPDATEquery(
            'tx_crawler_process',
            'active=0 AND deleted=0
            AND NOT EXISTS (
                SELECT * FROM tx_crawler_queue
                WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id
                AND tx_crawler_queue.exec_time = 0
            )',
            [
                'deleted' => '1',
                'system_process_id' => 0
            ]
        );
        // mark all requested processes as non-active
        $this->db->exec_UPDATEquery(
            'tx_crawler_process',
            'process_id IN (\'' . implode('\',\'', $releaseIds) . '\') AND deleted=0',
            [
                'active' => '0'
            ]
        );
        $this->db->exec_UPDATEquery(
            'tx_crawler_queue',
            'exec_time=0 AND process_id IN ("' . implode('","', $releaseIds) . '")',
            [
                'process_scheduled' => 0,
                'process_id' => ''
            ]
        );

        if (!$withinLock) {
            $this->db->sql_query('COMMIT');
        }

        return true;
    }

    /**
     * Delete processes marked as deleted
     *
     * @return void
     */
    public function CLI_deleteProcessesMarkedDeleted()
    {
        $this->db->exec_DELETEquery('tx_crawler_process', 'deleted = 1');
    }

    /**
     * Check if there are still resources left for the process with the given id
     * Used to determine timeouts and to ensure a proper cleanup if there's a timeout
     *
     * @param  string  identification string for the process
     * @return boolean determines if the process is still active / has resources
     *
     * FIXME: Please remove Transaction, not needed as only a select query.
     */
    public function CLI_checkIfProcessIsActive($pid)
    {
        $ret = false;
        $this->db->sql_query('BEGIN');
        $res = $this->db->exec_SELECTquery(
            'process_id,active,ttl',
            'tx_crawler_process',
            'process_id = \'' . $pid . '\'  AND deleted=0',
            '',
            'ttl',
            '0,1'
        );
        if ($row = $this->db->sql_fetch_assoc($res)) {
            $ret = intVal($row['active']) == 1;
        }
        $this->db->sql_query('COMMIT');

        return $ret;
    }

    /**
     * Create a unique Id for the current process
     *
     * @return string  the ID
     */
    public function CLI_buildProcessId()
    {
        if (!$this->processID) {
            $this->processID = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($this->microtime(true));
        }
        return $this->processID;
    }

    /**
     * @param bool $get_as_float
     *
     * @return mixed
     */
    protected function microtime($get_as_float = false)
    {
        return microtime($get_as_float);
    }

    /**
     * Prints a message to the stdout (only if debug-mode is enabled)
     *
     * @param  string $msg  the message
     */
    public function CLI_debug($msg)
    {
        if (intval($this->extensionSettings['processDebug'])) {
            echo $msg . "\n";
            flush();
        }
    }

    /**
     * Get URL content by making direct request to TYPO3.
     *
     * @param  string $url          Page URL
     * @param  int    $crawlerId    Crawler-ID
     * @return array
     */
    protected function sendDirectRequest($url, $crawlerId)
    {
        $requestHeaders = $this->buildRequestHeaderArray(parse_url($url), $crawlerId);

        $cmd = escapeshellcmd($this->extensionSettings['phpPath']);
        $cmd .= ' ';
        $cmd .= escapeshellarg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('crawler') . 'cli/bootstrap.php');
        $cmd .= ' ';
        $cmd .= escapeshellarg($this->getFrontendBasePath());
        $cmd .= ' ';
        $cmd .= escapeshellarg($url);
        $cmd .= ' ';
        $cmd .= escapeshellarg(base64_encode(serialize($requestHeaders)));

        $startTime = microtime(true);
        $content = $this->executeShellCommand($cmd);
        $this->log($url . ' ' . (microtime(true) - $startTime));

        $result = [
            'request' => implode("\r\n", $requestHeaders) . "\r\n\r\n",
            'headers' => '',
            'content' => $content
        ];

        return $result;
    }

    /**
     * Cleans up entries that stayed for too long in the queue. These are:
     * - processed entries that are over 1.5 days in age
     * - scheduled entries that are over 7 days old
     *
     * @return void
     */
    protected function cleanUpOldQueueEntries()
    {
        $processedAgeInSeconds = $this->extensionSettings['cleanUpProcessedAge'] * 86400; // 24*60*60 Seconds in 24 hours
        $scheduledAgeInSeconds = $this->extensionSettings['cleanUpScheduledAge'] * 86400;

        $now = time();
        $condition = '(exec_time<>0 AND exec_time<' . ($now - $processedAgeInSeconds) . ') OR scheduled<=' . ($now - $scheduledAgeInSeconds);
        $this->flushQueue($condition);
    }

    /**
     * Initializes a TypoScript Frontend necessary for using TypoScript and TypoLink functions
     *
     * @param int $id
     * @param int $typeNum
     *
     * @return void
     */
    protected function initTSFE($id = 1, $typeNum = 0)
    {
        \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();
        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
            $GLOBALS['TT']->start();
        }

        $GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], $id, $typeNum);
        $GLOBALS['TSFE']->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $GLOBALS['TSFE']->sys_page->init(true);
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($id, '');
        $GLOBALS['TSFE']->getConfigArray();
        \TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_lib.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/crawler/class.tx_crawler_lib.php']);
}
