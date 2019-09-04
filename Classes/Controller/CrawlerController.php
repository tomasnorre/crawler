<?php
namespace AOE\Crawler\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\EventDispatcher;
use AOE\Crawler\Utility\IconUtility;
use AOE\Crawler\Utility\SignalSlotUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class CrawlerController
 *
 * @package AOE\Crawler\Controller
 */
class CrawlerController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const CLI_STATUS_NOTHING_PROCCESSED = 0;
    const CLI_STATUS_REMAIN = 1; //queue not empty
    const CLI_STATUS_PROCESSED = 2; //(some) queue items where processed
    const CLI_STATUS_ABORTED = 4; //instance didn't finish
    const CLI_STATUS_POLLABLE_PROCESSED = 8;

    /**
     * @var integer
     */
    public $setID = 0;

    /**
     * @var string
     */
    public $processID = '';

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
     * @var bool
     */
    public $registerQueueEntriesInternallyOnly = false;

    /**
     * @var array
     */
    public $queueEntries = [];

    /**
     * @var array
     */
    public $urlList = [];

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
     * @var BackendUserAuthentication
     */
    private $backendUser;

    /**
     * @var integer
     */
    private $scheduledTime = 0;

    /**
     * @var integer
     */
    private $reqMinute = 0;

    /**
     * @var bool
     */
    private $submitCrawlUrls = false;

    /**
     * @var bool
     */
    private $downloadCrawlUrls = false;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var ProcessRepository
     */
    protected $processRepository;

    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_queue';


    /**
     * @var int
     */
    protected $maximumUrlsToCompile;

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
            GeneralUtility::writeFile($this->processFilename, '');
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
        return is_file($this->processFilename);
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
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
        $this->processRepository = $objectManager->get(ProcessRepository::class);

        $this->backendUser = $GLOBALS['BE_USER'];
        $this->processFilename = Environment::getVarPath() . '/locks/tx_crawler.proc';

        /** @var ExtensionConfigurationProvider $configurationProvider */
        $configurationProvider = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
        $settings = $configurationProvider->getExtensionConfiguration();
        $this->extensionSettings = is_array($settings) ? $settings : [];

        // set defaults:
        if (MathUtility::convertToPositiveInteger($this->extensionSettings['countInARun']) == 0) {
            $this->extensionSettings['countInARun'] = 100;
        }

        $this->extensionSettings['processLimit'] = MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1);
        $this->maximumUrlsToCompile = MathUtility::forceIntegerInRange($this->extensionSettings['maxCompileUrls'], 1, 1000000000, 10000);
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
            if (GeneralUtility::inList('3,4', $pageRow['doktype']) || $pageRow['doktype'] >= 199) {
                $skipPage = true;
                $skipMessage = 'Because doktype is not allowed';
            }
        }

        if (!$skipPage) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] ?? [] as $key => $doktypeList) {
                if (GeneralUtility::inList($doktypeList, $pageRow['doktype'])) {
                    $skipPage = true;
                    $skipMessage = 'Doktype was excluded by "' . $key . '"';
                    break;
                }
            }
        }

        if (!$skipPage) {
            // veto hook
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'] ?? [] as $key => $func) {
                $params = [
                    'pageRow' => $pageRow
                ];
                // expects "false" if page is ok and "true" or a skipMessage if this page should _not_ be crawled
                $veto = GeneralUtility::callUserFunction($func, $params, $this);
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
            $res = $this->getUrlsForPageId($pageRow['uid']);
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $noUnprocessedQueueEntriesFound = true;

        $result = $queryBuilder
            ->count('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('page_id', intval($uid)),
                $queryBuilder->expr()->eq('configuration_hash', $queryBuilder->createNamedParameter($configurationHash)),
                $queryBuilder->expr()->eq('exec_time', 0)
            )
            ->execute()
            ->fetchColumn();

        if ($result) {
            $noUnprocessedQueueEntriesFound = false;
        }

        return $noUnprocessedQueueEntriesFound;
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
        $urlList = '';

        if (is_array($vv['URLs'])) {
            $configurationHash = $this->getConfigurationHash($vv);
            $skipInnerCheck = $this->noUnprocessedQueueEntriesForPageWithConfigurationHashExist($pageRow['uid'], $configurationHash);

            foreach ($vv['URLs'] as $urlQuery) {
                if ($this->drawURLs_PIfilter($vv['subCfg']['procInstrFilter'], $incomingProcInstructions)) {

                    // Calculate cHash:
                    if ($vv['subCfg']['cHash']) {
                        /* @var $cacheHash \TYPO3\CMS\Frontend\Page\CacheHashCalculator */
                        $cacheHash = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\CacheHashCalculator');
                        $urlQuery .= '&cHash=' . $cacheHash->generateForParameters($urlQuery);
                    }

                    // Create key by which to determine unique-ness:
                    $uKey = $urlQuery . '|' . $vv['subCfg']['userGroups'] . '|' . $vv['subCfg']['baseUrl'] . '|' . $vv['subCfg']['procInstrFilter'];
                    $urlQuery = 'index.php' . $urlQuery;

                    // Scheduled time:
                    $schTime = $scheduledTime + round(count($duplicateTrack) * (60 / $reqMinute));
                    $schTime = floor($schTime / 60) * 60;

                    if (isset($duplicateTrack[$uKey])) {

                        //if the url key is registered just display it and do not resubmit is
                        $urlList = '<em><span class="typo3-dimmed">' . htmlspecialchars($urlQuery) . '</span></em><br/>';
                    } else {
                        $urlList = '[' . date('d.m.y H:i', $schTime) . '] ' . htmlspecialchars($urlQuery);
                        $this->urlList[] = '[' . date('d.m.y H:i', $schTime) . '] ' . $urlQuery;

                        $theUrl = ($vv['subCfg']['baseUrl'] ? $vv['subCfg']['baseUrl'] : GeneralUtility::getIndpEnv('TYPO3_SITE_URL')) . $urlQuery;

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
            if (GeneralUtility::inList($piString, $pi)) {
                return true;
            }
        }
        return false;
    }

    public function getPageTSconfigForId($id)
    {
        if (!$this->MP) {
            $pageTSconfig = BackendUtility::getPagesTSconfig($id);
        } else {
            [, $mountPointId] = explode('-', $this->MP);
            $pageTSconfig = BackendUtility::getPagesTSconfig($mountPointId);
        }

        // Call a hook to alter configuration
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'])) {
            $params = [
                'pageId' => $id,
                'pageTSConfig' => &$pageTSconfig
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'] as $userFunc) {
                GeneralUtility::callUserFunction($userFunc, $params, $this);
            }
        }
        return $pageTSconfig;
    }

    /**
     * This methods returns an array of configurations.
     * And no urls!
     *
     * @param integer $id Page ID
     * @return array
     */
    public function getUrlsForPageId($pageId)
    {
        // Get page TSconfig for page ID
        $pageTSconfig = $this->getPageTSconfigForId($pageId);

        $res = [];

        // Fetch Crawler Configuration from pageTSconfig
        $crawlerCfg = $pageTSconfig['tx_crawler.']['crawlerCfg.']['paramSets.'] ?? [];
        foreach ($crawlerCfg as $key => $values) {
            if (!is_array($values)) {
                continue;
            }
            $key = str_replace('.', '', $key);
            // Sub configuration for a single configuration string:
            $subCfg = (array)$crawlerCfg[$key . '.'];
            $subCfg['key'] = $key;

            if (strcmp($subCfg['procInstrFilter'], '')) {
                $subCfg['procInstrFilter'] = implode(',', GeneralUtility::trimExplode(',', $subCfg['procInstrFilter']));
            }
            $pidOnlyList = implode(',', GeneralUtility::trimExplode(',', $subCfg['pidsOnly'], true));

            // process configuration if it is not page-specific or if the specific page is the current page:
            if (!strcmp($subCfg['pidsOnly'], '') || GeneralUtility::inList($pidOnlyList, $pageId)) {

                    // add trailing slash if not present
                if (!empty($subCfg['baseUrl']) && substr($subCfg['baseUrl'], -1) != '/') {
                    $subCfg['baseUrl'] .= '/';
                }

                // Explode, process etc.:
                $res[$key] = [];
                $res[$key]['subCfg'] = $subCfg;
                $res[$key]['paramParsed'] = GeneralUtility::explodeUrl2Array($crawlerCfg[$key]);
                $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $pageId);
                $res[$key]['origin'] = 'pagets';

                // recognize MP value
                if (!$this->MP) {
                    $res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], ['?id=' . $pageId]);
                } else {
                    $res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], ['?id=' . $pageId . '&MP=' . $this->MP]);
                }
            }
        }

        // Get configuration from tx_crawler_configuration records up the rootline
        $crawlerConfigurations = $this->getCrawlerConfigurationRecordsFromRootLine($pageId);
        foreach ($crawlerConfigurations as $configurationRecord) {

                // check access to the configuration record
            if (empty($configurationRecord['begroups']) || $GLOBALS['BE_USER']->isAdmin() || $this->hasGroupAccess($GLOBALS['BE_USER']->user['usergroup_cached_list'], $configurationRecord['begroups'])) {
                $pidOnlyList = implode(',', GeneralUtility::trimExplode(',', $configurationRecord['pidsonly'], true));

                // process configuration if it is not page-specific or if the specific page is the current page:
                if (!strcmp($configurationRecord['pidsonly'], '') || GeneralUtility::inList($pidOnlyList, $pageId)) {
                    $key = $configurationRecord['name'];

                    // don't overwrite previously defined paramSets
                    if (!isset($res[$key])) {

                            /* @var $TSparserObject \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
                        $TSparserObject = GeneralUtility::makeInstance(TypoScriptParser::class);
                        $TSparserObject->parse($configurationRecord['processing_instruction_parameters_ts']);

                        $subCfg = [
                            'procInstrFilter' => $configurationRecord['processing_instruction_filter'],
                            'procInstrParams.' => $TSparserObject->setup,
                            'baseUrl' => $this->getBaseUrlForConfigurationRecord(
                                $configurationRecord['base_url'],
                                (int)$configurationRecord['sys_domain_base_url'],
                                (bool)($configurationRecord['force_ssl'] > 0)
                            ),
                            'cHash' => $configurationRecord['chash'],
                            'userGroups' => $configurationRecord['fegroups'],
                            'exclude' => $configurationRecord['exclude'],
                            'rootTemplatePid' => (int) $configurationRecord['root_template_pid'],
                            'key' => $key
                        ];

                        // add trailing slash if not present
                        if (!empty($subCfg['baseUrl']) && substr($subCfg['baseUrl'], -1) != '/') {
                            $subCfg['baseUrl'] .= '/';
                        }
                        if (!in_array($pageId, $this->expandExcludeString($subCfg['exclude']))) {
                            $res[$key] = [];
                            $res[$key]['subCfg'] = $subCfg;
                            $res[$key]['paramParsed'] = GeneralUtility::explodeUrl2Array($configurationRecord['configuration']);
                            $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $pageId);
                            $res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], ['?id=' . $pageId]);
                            $res[$key]['origin'] = 'tx_crawler_configuration_' . $configurationRecord['uid'];
                        }
                    }
                }
            }
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['processUrls'] ?? [] as $func) {
            $params = [
                'res' => &$res,
            ];
            GeneralUtility::callUserFunction($func, $params, $this);
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
    protected function getBaseUrlForConfigurationRecord(string $baseUrl, int $sysDomainUid, bool $ssl = false): string
    {
        if ($sysDomainUid > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_domain');
            $domainName = $queryBuilder
                ->select('domainName')
                ->from('sys_domain')
                ->where(
                    $queryBuilder->expr()->eq('uid', $sysDomainUid)
                )
                ->execute()
                ->fetchColumn();

            if (!empty($domainName)) {
                $baseUrl = ($ssl ? 'https' : 'http') . '://' . $domainName;
            }
        }
        return $baseUrl;
    }

    /**
     * Find all configurations of subpages of a page
     *
     * @param int $rootid
     * @param $depth
     * @return array
     *
     * TODO: Write Functional Tests
     */
    public function getConfigurationsForBranch(int $rootid, $depth)
    {
        $configurationsForBranch = [];
        $pageTSconfig = $this->getPageTSconfigForId($rootid);
        $sets = $pageTSconfig['tx_crawler.']['crawlerCfg.']['paramSets.'] ?? [];
        foreach ($sets as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            $configurationsForBranch[] = substr($key, -1) == '.' ? substr($key, 0, -1) : $key;
        }
        $pids = [];
        $rootLine = BackendUtility::BEgetRootLine($rootid);
        foreach ($rootLine as $node) {
            $pids[] = $node['uid'];
        }
        /* @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
        $tree->init('AND ' . $perms_clause);
        $tree->getTree($rootid, $depth, '');
        foreach ($tree->tree as $node) {
            $pids[] = $node['row']['uid'];
        }

        $queryBuilder = $this->getQueryBuilder('tx_crawler_configuration');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select('name')
            ->from('tx_crawler_configuration')
            ->where(
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pids, Connection::PARAM_INT_ARRAY))
            )
            ->execute();

        while ($row = $statement->fetch()) {
            $configurationsForBranch[] = $row['name'];
        }
        return $configurationsForBranch;
    }

    /**
     * Get querybuilder for given table
     *
     * @param string $table
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getQueryBuilder(string $table)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
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
        foreach (GeneralUtility::intExplode(',', $groupList) as $groupUid) {
            if (GeneralUtility::inList($accessList, $groupUid)) {
                return true;
            }
        }
        return false;
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
     *
     * TODO: Write Functional Tests
     */
    public function expandParameters($paramArray, $pid)
    {
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
                        $subparts = GeneralUtility::trimExplode(';', $pV);
                        $subpartParams = [];
                        foreach ($subparts as $spV) {
                            list($pKey, $pVal) = GeneralUtility::trimExplode(':', $spV);
                            $subpartParams[$pKey] = $pVal;
                        }

                        // Table exists:
                        if (isset($GLOBALS['TCA'][$subpartParams['_TABLE']])) {
                            $lookUpPid = isset($subpartParams['_PID']) ? intval($subpartParams['_PID']) : $pid;
                            $pidField = isset($subpartParams['_PIDFIELD']) ? trim($subpartParams['_PIDFIELD']) : 'pid';
                            $where = isset($subpartParams['_WHERE']) ? $subpartParams['_WHERE'] : '';
                            $addTable = isset($subpartParams['_ADDTABLE']) ? $subpartParams['_ADDTABLE'] : '';

                            $fieldName = $subpartParams['_FIELD'] ? $subpartParams['_FIELD'] : 'uid';
                            if ($fieldName === 'uid' || $GLOBALS['TCA'][$subpartParams['_TABLE']]['columns'][$fieldName]) {
                                $queryBuilder = $this->getQueryBuilder($subpartParams['_TABLE']);

                                $queryBuilder->getRestrictions()
                                    ->removeAll()
                                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

                                $queryBuilder
                                    ->select($fieldName)
                                    ->from($subpartParams['_TABLE'])
                                    // TODO: Check if this works as intended!
                                    ->add('from', $addTable)
                                    ->where(
                                        $queryBuilder->expr()->eq($queryBuilder->quoteIdentifier($pidField), $queryBuilder->createNamedParameter($lookUpPid, \PDO::PARAM_INT)),
                                        $where
                                    );
                                $transOrigPointerField = $GLOBALS['TCA'][$subpartParams['_TABLE']]['ctrl']['transOrigPointerField'];

                                if ($subpartParams['_ENABLELANG'] && $transOrigPointerField) {
                                    $queryBuilder->andWhere(
                                        $queryBuilder->expr()->lte(
                                            $queryBuilder->quoteIdentifier($transOrigPointerField),
                                            0
                                        )
                                    );
                                }

                                $statement = $queryBuilder->execute();

                                $rows = [];
                                while ($row = $statement->fetch()) {
                                    $rows[$fieldName] = $row;
                                }

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
                            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
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
    public function compileUrls($paramArray, array $urls)
    {
        if (empty($paramArray)) {
            return $urls;
        }
        // shift first off stack:
        reset($paramArray);
        $varName = key($paramArray);
        $valueSet = array_shift($paramArray);

        // Traverse value set:
        $newUrls = [];
        foreach ($urls as $url) {
            foreach ($valueSet as $val) {
                $newUrls[] = $url . (strcmp($val, '') ? '&' . rawurlencode($varName) . '=' . rawurlencode($val) : '');

                if (count($newUrls) > $this->maximumUrlsToCompile) {
                    break;
                }
            }
        }
        return $this->compileUrls($paramArray, $newUrls);
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )
            ->orderBy('scheduled', 'DESC');

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tableName)
            ->getExpressionBuilder();
        $query = $expressionBuilder->andX();
        // PHPStorm adds the highlight that the $addWhere is immediately overwritten,
        // but the $query = $expressionBuilder->andX() ensures that the $addWhere is written correctly with AND
        // between the statements, it's not a mistake in the code.
        $addWhere = '';
        switch ($filter) {
            case 'pending':
                $queryBuilder->andWhere($queryBuilder->expr()->eq('exec_time', 0));
                $addWhere = ' AND ' . $query->add($expressionBuilder->eq('exec_time', 0));
                break;
            case 'finished':
                $queryBuilder->andWhere($queryBuilder->expr()->gt('exec_time', 0));
                $addWhere = ' AND ' . $query->add($expressionBuilder->gt('exec_time', 0));
                break;
        }

        // FIXME: Write unit test that ensures that the right records are deleted.
        if ($doFlush) {
            $addWhere = $query->add($expressionBuilder->eq('page_id', intval($id)));
            $this->flushQueue($doFullFlush ? '1=1' : $addWhere);
            return [];
        } else {
            if ($itemsPerPage > 0) {
                $queryBuilder
                    ->setMaxResults((int)$itemsPerPage);
            }

            return $queryBuilder->execute()->fetchAll();
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('set_id', $queryBuilder->createNamedParameter($set_id, \PDO::PARAM_INT))
            )
            ->orderBy('scheduled', 'DESC');

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tableName)
            ->getExpressionBuilder();
        $query = $expressionBuilder->andX();
        // FIXME: Write Unit tests for Filters
        // PHPStorm adds the highlight that the $addWhere is immediately overwritten,
        // but the $query = $expressionBuilder->andX() ensures that the $addWhere is written correctly with AND
        // between the statements, it's not a mistake in the code.
        $addWhere = '';
        switch ($filter) {
            case 'pending':
                $queryBuilder->andWhere($queryBuilder->expr()->eq('exec_time', 0));
                $addWhere = $query->add($expressionBuilder->eq('exec_time', 0));
                break;
            case 'finished':
                $queryBuilder->andWhere($queryBuilder->expr()->gt('exec_time', 0));
                $addWhere = $query->add($expressionBuilder->gt('exec_time', 0));
                break;
        }
        // FIXME: Write unit test that ensures that the right records are deleted.
        if ($doFlush) {
            $addWhere = $query->add($expressionBuilder->eq('set_id', intval($set_id)));
            $this->flushQueue($doFullFlush ? '' : $addWhere);
            return [];
        } else {
            if ($itemsPerPage > 0) {
                $queryBuilder
                    ->setMaxResults((int)$itemsPerPage);
            }

            return $queryBuilder->execute()->fetchAll();
        }
    }

    /**
     * Removes queue entries
     *
     * @param string $where SQL related filter for the entries which should be removed
     * @return void
     */
    protected function flushQueue($where = '')
    {
        $realWhere = strlen($where) > 0 ? $where : '1=1';

        $queryBuilder = $this->getQueryBuilder($this->tableName);

        if (EventDispatcher::getInstance()->hasObserver('queueEntryFlush')) {
            $groups = $queryBuilder
                ->select('DISTINCT set_id')
                ->from($this->tableName)
                ->where($realWhere)
                ->execute()
                ->fetchAll();
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    $subSet = $queryBuilder
                        ->select('uid', 'set_id')
                        ->from($this->tableName)
                        ->where(
                            $realWhere,
                            $queryBuilder->expr()->eq('set_id', $group['set_id'])
                        )
                        ->execute()
                        ->fetchAll();
                    EventDispatcher::getInstance()->post('queueEntryFlush', $group['set_id'], $subSet);
                }
            }
        }

        $queryBuilder
            ->delete($this->tableName)
            ->where($realWhere)
            ->execute();
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

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_queue')
            ->insert(
                'tx_crawler_queue',
                [
                    'page_id' => intval($page_id),
                    'parameters' => serialize($params),
                    'scheduled' => intval($schedule) ? intval($schedule) : $this->getCurrentTime(),
                    'exec_time' => 0,
                    'set_id' => intval($setId),
                    'result_data' => '',
                ]
            );
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
        $rows = [];

        // Creating parameters:
        $parameters = [
            'url' => $url
        ];

        // fe user group simulation:
        $uGs = implode(',', array_unique(GeneralUtility::intExplode(',', $subCfg['userGroups'], true)));
        if ($uGs) {
            $parameters['feUserGroupList'] = $uGs;
        }

        // Setting processing instructions
        $parameters['procInstructions'] = GeneralUtility::trimExplode(',', $subCfg['procInstrFilter']);
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
            'parameters_hash' => GeneralUtility::shortMD5($parameters_serialized),
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

            if (empty($rows)) {
                $connectionForCrawlerQueue = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_queue');
                $connectionForCrawlerQueue->insert(
                    'tx_crawler_queue',
                    $fieldArray
                );
                $uid = $connectionForCrawlerQueue->lastInsertId('tx_crawler_queue', 'qid');
                $rows[] = $uid;
                $urlAdded = true;
                EventDispatcher::getInstance()->post('urlAddedToQueue', $this->setID, ['uid' => $uid, 'fieldArray' => $fieldArray]);
            } else {
                EventDispatcher::getInstance()->post('duplicateUrlInQueue', $this->setID, ['rows' => $rows, 'fieldArray' => $fieldArray]);
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
     * @return array
     *
     * TODO: Write Functional Tests
     */
    protected function getDuplicateRowsIfExist($tstamp, $fieldArray)
    {
        $rows = [];

        $currentTime = $this->getCurrentTime();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->select('qid')
            ->from('tx_crawler_queue');
        //if this entry is scheduled with "now"
        if ($tstamp <= $currentTime) {
            if ($this->extensionSettings['enableTimeslot']) {
                $timeBegin = $currentTime - 100;
                $timeEnd = $currentTime + 100;
                $queryBuilder
                    ->where(
                        'scheduled BETWEEN ' . $timeBegin . ' AND ' . $timeEnd . ''
                    )
                    ->orWhere(
                        $queryBuilder->expr()->lte('scheduled', $currentTime)
                    );
            } else {
                $queryBuilder
                    ->where(
                        $queryBuilder->expr()->lte('scheduled', $currentTime)
                    );
            }
        } elseif ($tstamp > $currentTime) {
            //entry with a timestamp in the future need to have the same schedule time
            $queryBuilder
                ->where(
                    $queryBuilder->expr()->eq('scheduled', $tstamp)
                );
        }

        $statement = $queryBuilder
            ->andWhere('exec_time != 0')
            ->andWhere('process_id != 0')
            ->andWhere($queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($fieldArray['page_id'], \PDO::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('parameters_hash', $queryBuilder->createNamedParameter($fieldArray['parameters_hash'], \PDO::PARAM_STR)))
            ->execute();

        while ($row = $statement->fetch()) {
            $rows[] = $row['qid'];
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $ret = 0;
        $this->logger->debug('crawler-readurl start ' . microtime(true));
        // Get entry:
        $queryBuilder
            ->select('*')
            ->from('tx_crawler_queue')
            ->where(
                $queryBuilder->expr()->eq('qid', $queryBuilder->createNamedParameter($queueId, \PDO::PARAM_INT))
            );
        if (!$force) {
            $queryBuilder
                ->andWhere('exec_time = 0')
                ->andWhere('process_scheduled > 0');
        }
        $queueRec = $queryBuilder->execute()->fetch();

        if (!is_array($queueRec)) {
            return;
        }

        $parameters = unserialize($queueRec['parameters']);
        if ($parameters['rootTemplatePid']) {
            $this->initTSFE((int)$parameters['rootTemplatePid']);
        } else {
            $this->logger->warning(
                'Page with (' . $queueRec['page_id'] . ') could not be crawled, please check your crawler configuration. Perhaps no Root Template Pid is set'
            );
        }

        SignalSlotUtility::emitSignal(
            __CLASS__,
            SignalSlotUtility::SIGNNAL_QUEUEITEM_PREPROCESS,
            [$queueId, &$queueRec]
        );

        // Set exec_time to lock record:
        $field_array = ['exec_time' => $this->getCurrentTime()];

        if (isset($this->processID)) {
            //if mulitprocessing is used we need to store the id of the process which has handled this entry
            $field_array['process_id_completed'] = $this->processID;
        }

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_queue')
            ->update(
                'tx_crawler_queue',
                $field_array,
                [ 'qid' => (int)$queueId ]
            );

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

        SignalSlotUtility::emitSignal(
            __CLASS__,
            SignalSlotUtility::SIGNNAL_QUEUEITEM_POSTPROCESS,
            [$queueId, &$field_array]
        );

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_queue')
            ->update(
                'tx_crawler_queue',
                $field_array,
                [ 'qid' => (int)$queueId ]
            );

        $this->logger->debug('crawler-readurl stop ' . microtime(true));
        return $ret;
    }

    /**
     * Read URL for not-yet-inserted log-entry
     *
     * @param array $field_array Queue field array,
     *
     * @return string
     */
    public function readUrlFromArray($field_array)
    {

            // Set exec_time to lock record:
        $field_array['exec_time'] = $this->getCurrentTime();
        $connectionForCrawlerQueue = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_queue');
        $connectionForCrawlerQueue->insert(
            'tx_crawler_queue',
            $field_array
        );
        $queueId = $field_array['qid'] = $connectionForCrawlerQueue->lastInsertId('tx_crawler_queue', 'qid');

        $result = $this->readUrl_exec($field_array);

        // Set result in log which also denotes the end of the processing of this entry.
        $field_array = ['result_data' => serialize($result)];

        SignalSlotUtility::emitSignal(
            __CLASS__,
            SignalSlotUtility::SIGNNAL_QUEUEITEM_POSTPROCESS,
            [$queueId, &$field_array]
        );

        $connectionForCrawlerQueue->update(
            'tx_crawler_queue',
            $field_array,
            ['qid' => $queueId]
        );

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
                $callBackObj = GeneralUtility::makeInstance($objRef);
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

                EventDispatcher::getInstance()->post('urlCrawled', $queueRec['set_id'], ['url' => $parameters['url'], 'result' => $result]);
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
     * @return array|boolean
     */
    public function requestUrl($originalUrl, $crawlerId, $timeout = 2, $recursion = 10)
    {
        if (!$recursion) {
            return false;
        }

        // Parse URL, checking for scheme:
        $url = parse_url($originalUrl);

        if ($url === false) {
            $this->logger->debug(
                sprintf('Could not parse_url() for string "%s"', $url),
                ['crawlerId' => $crawlerId]
            );
            return false;
        }

        if (!in_array($url['scheme'], ['','http','https'])) {
            $this->logger->debug(
                sprintf('Scheme does not match for url "%s"', $url),
                ['crawlerId' => $crawlerId]
            );
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
            $this->logger->debug(
                sprintf('Error while opening "%s"', $url),
                ['crawlerId' => $crawlerId]
            );
            return false;
        } else {
            // Request message:
            $msg = implode("\r\n", $reqHeaders) . "\r\n\r\n";
            fputs($fp, $msg);

            // Read response:
            $d = $this->getHttpResponseFromStream($fp);
            fclose($fp);

            $time = microtime(true) - $startTime;
            $this->logger->info($originalUrl . ' ' . $time);

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
                    $this->logger->debug(
                        sprintf('Error while opening "%s"', $url),
                        ['crawlerId' => $crawlerId]
                    );
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
        } elseif (!Environment::isCli()) {
            $frontendBasePath = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        }

        // Base path must be '/<pathSegements>/':
        if ($frontendBasePath !== '/') {
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
        return shell_exec($command);
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
     * @return bool|string
     */
    protected function getRequestUrlFrom302Header($headers, $user = '', $pass = '')
    {
        $header = [];
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
     *
     * FIXME: Look like this is not used, in commit 9910d3f40cce15f4e9b7bcd0488bf21f31d53ebc it's added as public,
     * FIXME: I think this can be removed. (TNM)
     */
    public function fe_init(&$params, $ref)
    {
        // Authenticate crawler request:
        if (isset($_SERVER['HTTP_X_T3CRAWLER'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
            list($queueId, $hash) = explode(':', $_SERVER['HTTP_X_T3CRAWLER']);

            $queueRec = $queryBuilder
                ->select('*')
                ->from('tx_crawler_queue')
                ->where(
                    $queryBuilder->expr()->eq('qid', $queryBuilder->createNamedParameter($queueId, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetch();

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
        $this->scheduledTime = $scheduledTime;
        $this->reqMinute = $reqMinute;
        $this->submitCrawlUrls = $submitCrawlUrls;
        $this->downloadCrawlUrls = $downloadCrawlUrls;
        $this->incomingProcInstructions = $incomingProcInstructions;
        $this->incomingConfigurationSelection = $configurationSelection;

        $this->duplicateTrack = [];
        $this->downloadUrls = [];

        // Drawing tree:
        /* @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
        $tree->init('AND ' . $perms_clause);

        $pageInfo = BackendUtility::readPageAccess($id, $perms_clause);
        if (is_array($pageInfo)) {
            // Set root row:
            $tree->tree[] = [
                'row' => $pageInfo,
                'HTML' => IconUtility::getIconForRecord('pages', $pageInfo)
            ];
        }

        // Get branch beneath:
        if ($depth) {
            $tree->getTree($id, $depth, '');
        }

        // Traverse page tree:
        $code = '';

        foreach ($tree->tree as $data) {
            $this->MP = false;

            // recognize mount points
            if ($data['row']['doktype'] == PageRepository::DOKTYPE_MOUNTPOINT) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $mountpage = $queryBuilder
                    ->select('*')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($data['row']['uid'], \PDO::PARAM_INT))
                    )
                    ->execute()
                    ->fetchAll();
                $queryBuilder->getRestrictions()->reset();

                // fetch mounted pages
                $this->MP = $mountpage[0]['mount_pid'] . '-' . $data['row']['uid'];

                $mountTree = GeneralUtility::makeInstance(PageTreeView::class);
                $mountTree->init('AND ' . $perms_clause);
                $mountTree->getTree($mountpage[0]['mount_pid'], $depth);

                foreach ($mountTree->tree as $mountData) {
                    $code .= $this->drawURLs_addRowsForPage(
                        $mountData['row'],
                        $mountData['HTML'] . BackendUtility::getRecordTitle('pages', $mountData['row'], true)
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
                $data['HTML'] . BackendUtility::getRecordTitle('pages', $data['row'], true)
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
                /** @var PageTreeView $tree */
                $tree = GeneralUtility::makeInstance(PageTreeView::class);
                $tree->init('AND ' . $this->backendUser->getPagePermsClause(1));

                $excludeParts = GeneralUtility::trimExplode(',', $excludeString);

                foreach ($excludeParts as $excludePart) {
                    list($pid, $depth) = GeneralUtility::trimExplode('+', $excludePart);

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

        if (!empty($this->incomingConfigurationSelection)) {
            // remove configuration that does not match the current selection
            foreach ($configurations as $confKey => $confArray) {
                if (!in_array($confKey, $this->incomingConfigurationSelection)) {
                    unset($configurations[$confKey]);
                }
            }
        }

        // Traverse parameter combinations:
        $c = 0;
        $content = '';
        if (!empty($configurations)) {
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
                            <td>' . nl2br(htmlspecialchars(rawurldecode(trim(str_replace('&', chr(10) . '&', GeneralUtility::implodeArrayForUrl('', $confArray['paramParsed'])))))) . '</td>
                            <td>' . $paramExpanded . '</td>
                            <td nowrap="nowrap">' . $urlList . '</td>
                            <td nowrap="nowrap">' . $optionValues . '</td>
                            <td nowrap="nowrap">' . DebugUtility::viewArray($confArray['subCfg']['procInstrParams.']) . '</td>
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

    /*****************************
     *
     * CLI functions
     *
     *****************************/

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

            $queryBuilderDelete = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
            $del = $queryBuilderDelete
                ->delete($this->tableName)
                ->where(
                    'exec_time != 0 AND exec_time < ' . $purgeDate
                )->execute();

            if (false === $del) {
                $this->logger->info(
                    'Records could not be deleted.'
                );
            }
        }

        // Select entries:
        //TODO Shouldn't this reside within the transaction?
        $queryBuilderSelect = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $rows = $queryBuilderSelect
            ->select('qid', 'scheduled')
            ->from('tx_crawler_queue')
            ->where(
                $queryBuilderSelect->expr()->eq('exec_time', 0),
                $queryBuilderSelect->expr()->eq('process_scheduled', 0),
                $queryBuilderSelect->expr()->lte('scheduled', $this->getCurrentTime())
            )
            ->orderBy('scheduled')
            ->addOrderBy('qid')
            ->setMaxResults($countInARun)
            ->execute()
            ->fetchAll();

        if (!empty($rows)) {
            $quidList = [];

            foreach ($rows as $r) {
                $quidList[] = $r['qid'];
            }

            $processId = $this->CLI_buildProcessId();

            //reserve queue entries for process

            //$this->queryBuilder->getConnection()->executeQuery('BEGIN');
            //TODO make sure we're not taking assigned queue-entires

            //save the number of assigned queue entrys to determine who many have been processed later
            $queryBuilderUpdate = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
            $numberOfAffectedRows = $queryBuilderUpdate
                ->update('tx_crawler_queue')
                ->where(
                    $queryBuilderUpdate->expr()->in('qid', $quidList)
                )
                ->set('process_scheduled', $this->getCurrentTime())
                ->set('process_id', $queryBuilderUpdate->createNamedParameter($processId, \PDO::PARAM_STR))
                ->execute();

            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_process')
                ->update(
                    'tx_crawler_process',
                    [ 'assigned_items_count' => (int)$numberOfAffectedRows ],
                    [ 'process_id' => (int) $processId ]
                );

            if ($numberOfAffectedRows == count($quidList)) {
                //$this->queryBuilder->getConnection()->executeQuery('COMMIT');
            } else {
                //$this->queryBuilder->getConnection()->executeQuery('ROLLBACK');
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['cli_hooks'] ?? [] as $objRef) {
            $hookObj = GeneralUtility::makeInstance($objRef);
            if (is_object($hookObj)) {
                $hookObj->crawler_init($this);
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $ret = true;

        $systemProcessId = getmypid();
        if ($systemProcessId < 1) {
            return false;
        }

        $processCount = 0;
        $orphanProcesses = [];

        //$this->queryBuilder->getConnection()->executeQuery('BEGIN');

        $statement = $queryBuilder
            ->select('process_id', 'ttl')
            ->from('tx_crawler_process')
            ->where(
                'active = 1 AND deleted = 0'
            )
            ->execute();

        $currentTime = $this->getCurrentTime();

        while ($row = $statement->fetch()) {
            if ($row['ttl'] < $currentTime) {
                $orphanProcesses[] = $row['process_id'];
            } else {
                $processCount++;
            }
        }

        // if there are less than allowed active processes then add a new one
        if ($processCount < intval($this->extensionSettings['processLimit'])) {
            $this->CLI_debug("add process " . $this->CLI_buildProcessId() . " (" . ($processCount + 1) . "/" . intval($this->extensionSettings['processLimit']) . ")");

            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_process')->insert(
                'tx_crawler_process',
                [
                    'process_id' => $id,
                    'active' => 1,
                    'ttl' => $currentTime + (int)$this->extensionSettings['processMaxRunTime'],
                    'system_process_id' => $systemProcessId
                ]
            );
        } else {
            $this->CLI_debug("Processlimit reached (" . ($processCount) . "/" . intval($this->extensionSettings['processLimit']) . ")");
            $ret = false;
        }

        $this->processRepository->deleteProcessesMarkedAsDeleted();
        $this->processRepository->deleteProcessesWithoutItemsAssigned();
        $this->CLI_releaseProcesses($orphanProcesses, true); // maybe this should be somehow included into the current lock

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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        if (!is_array($releaseIds)) {
            $releaseIds = [$releaseIds];
        }

        if (empty($releaseIds)) {
            return false;   //nothing to release
        }

        if (!$withinLock) {
            //$this->queryBuilder->getConnection()->executeQuery('BEGIN');
        }

        // some kind of 2nd chance algo - this way you need at least 2 processes to have a real cleanup
        // this ensures that a single process can't mess up the entire process table

        // mark all processes as deleted which have no "waiting" queue-entires and which are not active

        $queryBuilder
        ->update('tx_crawler_queue', 'q')
        ->where(
            'q.process_id IN(SELECT p.process_id FROM tx_crawler_process as p WHERE p.active = 0)'
        )
        ->set('q.process_scheduled', 0)
        ->set('q.process_id', '')
        ->execute();

        // FIXME: Not entirely sure that this is equivalent to the previous version
        $queryBuilder->resetQueryPart('set');

        $queryBuilder
            ->update('tx_crawler_process')
            ->where(
                $queryBuilder->expr()->eq('active', 0),
                'process_id IN(SELECT q.process_id FROM tx_crawler_queue as q WHERE q.exec_time = 0)'
            )
            ->set('system_process_id', 0)
            ->execute();
        // previous version for reference
        /*
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
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
        );*/
        // mark all requested processes as non-active
        $queryBuilder
            ->update('tx_crawler_process')
            ->where(
                'NOT EXISTS (
                SELECT * FROM tx_crawler_queue
                    WHERE tx_crawler_queue.process_id = tx_crawler_process.process_id
                    AND tx_crawler_queue.exec_time = 0
                )',
                $queryBuilder->expr()->in('process_id', $queryBuilder->createNamedParameter($releaseIds, Connection::PARAM_STR_ARRAY)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->set('active', 0)
            ->execute();
        $queryBuilder->resetQueryPart('set');
        $queryBuilder
            ->update('tx_crawler_queue')
            ->where(
                $queryBuilder->expr()->eq('exec_time', 0),
                $queryBuilder->expr()->in('process_id', $queryBuilder->createNamedParameter($releaseIds, Connection::PARAM_STR_ARRAY))
            )
            ->set('process_scheduled', 0)
            ->set('process_id', '')
            ->execute();

        if (!$withinLock) {
            //$this->queryBuilder->getConnection()->executeQuery('COMMIT');
        }

        return true;
    }

    /**
     * Check if there are still resources left for the process with the given id
     * Used to determine timeouts and to ensure a proper cleanup if there's a timeout
     *
     * @param  string  identification string for the process
     * @return boolean determines if the process is still active / has resources
     *
     * TODO: Please consider moving this to Domain Model for Process or in ProcessRepository
     */
    public function CLI_checkIfProcessIsActive($pid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $ret = false;

        $statement = $queryBuilder
            ->from('tx_crawler_process')
            ->select('active')
            ->where(
                $queryBuilder->expr()->eq('process_id', intval($pid))
            )
            ->orderBy('ttl')
            ->execute();

        if ($row = $statement->fetch(0)) {
            $ret = intVal($row['active']) == 1;
        }

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
            $this->processID = GeneralUtility::shortMD5($this->microtime(true));
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
        $parsedUrl = parse_url($url);
        if (!is_array($parsedUrl)) {
            return [];
        }

        $requestHeaders = $this->buildRequestHeaderArray($parsedUrl, $crawlerId);

        $cmd = escapeshellcmd($this->extensionSettings['phpPath']);
        $cmd .= ' ';
        $cmd .= escapeshellarg(ExtensionManagementUtility::extPath('crawler') . 'cli/bootstrap.php');
        $cmd .= ' ';
        $cmd .= escapeshellarg($this->getFrontendBasePath());
        $cmd .= ' ';
        $cmd .= escapeshellarg($url);
        $cmd .= ' ';
        $cmd .= escapeshellarg(base64_encode(serialize($requestHeaders)));

        $startTime = microtime(true);
        $content = $this->executeShellCommand($cmd);
        $this->logger->info($url . ' ' . (microtime(true) - $startTime));

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
    public function cleanUpOldQueueEntries()
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
     * @param int $pageId
     * @return void
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     * @throws \TYPO3\CMS\Core\Http\ImmediateResponseException
     */
    protected function initTSFE(int $pageId): void
    {
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            null,
            $pageId,
            0
        );
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->getConfigArray();
        $GLOBALS['TSFE']->settingLanguage();
        $GLOBALS['TSFE']->settingLocale();
        $GLOBALS['TSFE']->newCObj();
    }

    /**
     * Returns a md5 hash generated from a serialized configuration array.
     *
     * @param array $configuration
     *
     * @return string
     */
    protected function getConfigurationHash(array $configuration)
    {
        unset($configuration['paramExpanded']);
        unset($configuration['URLs']);
        return md5(serialize($configuration));
    }

    /**
     * Traverses up the rootline of a page and fetches all crawler records
     * @param int $pageId
     * @return array
     */
    protected function getCrawlerConfigurationRecordsFromRootLine(int $pageId): array
    {
        $rootLine = BackendUtility::BEgetRootLine($pageId);

        $pageIdsInRootLine = [];
        foreach ($rootLine as $pageInRootLine) {
            $pageIdsInRootLine[] = (int)$pageInRootLine['uid'];
        }

        $queryBuilder = $this->getQueryBuilder('tx_crawler_configuration');
        $queryBuilder
            ->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $configurationRecordsForCurrentPage = $queryBuilder
            ->select('*')
            ->from('tx_crawler_configuration')
            ->where(
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pageIdsInRootLine, Connection::PARAM_INT_ARRAY))
            )
            ->execute()
            ->fetchAll();
        return is_array($configurationRecordsForCurrentPage) ? $configurationRecordsForCurrentPage : [];
    }
}
