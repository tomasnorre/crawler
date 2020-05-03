<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 AOE GmbH <dev@aoe.com>
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
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Utility\SignalSlotUtility;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class CrawlerController
 *
 * @package AOE\Crawler\Controller
 */
class CrawlerController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PublicMethodDeprecationTrait;

    public const CLI_STATUS_NOTHING_PROCCESSED = 0;

    public const CLI_STATUS_REMAIN = 1; //queue not empty

    public const CLI_STATUS_PROCESSED = 2; //(some) queue items where processed

    public const CLI_STATUS_ABORTED = 4; //instance didn't finish

    public const CLI_STATUS_POLLABLE_PROCESSED = 8;

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
     * @var bool
     * Todo: Check what this is used for and adjust the type hint or code, as bool doesn't match the current code.
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
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var ProcessRepository
     */
    protected $processRepository;

    /**
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var string
     */
    protected $tableName = 'tx_crawler_queue';

    /**
     * @var QueueExecutor
     */
    protected $queueExecutor;

    /**
     * @var int
     */
    protected $maximumUrlsToCompile = 10000;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var string[]
     */
    private $deprecatedPublicMethods = [
        'getLogEntriesForSetId' => 'Using crawlerController::getLogEntriesForSetId() is deprecated since 9.0.1 and will be removed in v11.x',
        'flushQueue' => 'Using CrawlerController::flushQueue() is deprecated since 9.0.1 and will be removed in v11.x, please use QueueRepository->flushQueue() instead.',
        'cleanUpOldQueueEntries' => 'Using CrawlerController::cleanUpOldQueueEntries() is deprecated since 9.0.1 and will be removed in v11.x, please use QueueRepository->cleanUpOldQueueEntries() instead.',
    ];

    /**
     * @var BackendUserAuthentication|null
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
        $this->configurationRepository = $objectManager->get(ConfigurationRepository::class);
        $this->queueExecutor = $objectManager->get(QueueExecutor::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $this->processFilename = Environment::getVarPath() . '/lock/tx_crawler.proc';

        /** @var ExtensionConfigurationProvider $configurationProvider */
        $configurationProvider = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
        $settings = $configurationProvider->getExtensionConfiguration();
        $this->extensionSettings = is_array($settings) ? $settings : [];

        // set defaults:
        if (MathUtility::convertToPositiveInteger($this->extensionSettings['countInARun']) === 0) {
            $this->extensionSettings['countInARun'] = 100;
        }

        $this->extensionSettings['processLimit'] = MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1);
        $this->maximumUrlsToCompile = MathUtility::forceIntegerInRange($this->extensionSettings['maxCompileUrls'], 1, 1000000000, 10000);
    }

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
    public function setAccessMode($accessMode): void
    {
        $this->accessMode = $accessMode;
    }

    /**
     * Set disabled status to prevent processes from being processed
     *
     * @param bool $disabled (optional, defaults to true)
     */
    public function setDisabled($disabled = true): void
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
     */
    public function setProcessFilename($filenameWithPath): void
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

    /**
     * Sets the extensions settings (unserialized pendant of $TYPO3_CONF_VARS['EXT']['extConf']['crawler']).
     */
    public function setExtensionSettings(array $extensionSettings): void
    {
        $this->extensionSettings = $extensionSettings;
    }

    /**
     * Check if the given page should be crawled
     *
     * @return false|string false if the page should be crawled (not excluded), true / skipMessage if it should be skipped
     */
    public function checkIfPageShouldBeSkipped(array $pageRow)
    {
        $skipPage = false;
        $skipMessage = 'Skipped'; // message will be overwritten later

        // if page is hidden
        if (! $this->extensionSettings['crawlHiddenPages']) {
            if ($pageRow['hidden']) {
                $skipPage = true;
                $skipMessage = 'Because page is hidden';
            }
        }

        if (! $skipPage) {
            if (GeneralUtility::inList('3,4', $pageRow['doktype']) || $pageRow['doktype'] >= 199) {
                $skipPage = true;
                $skipMessage = 'Because doktype is not allowed';
            }
        }

        if (! $skipPage) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'] ?? [] as $key => $doktypeList) {
                if (GeneralUtility::inList($doktypeList, $pageRow['doktype'])) {
                    $skipPage = true;
                    $skipMessage = 'Doktype was excluded by "' . $key . '"';
                    break;
                }
            }
        }

        if (! $skipPage) {
            // veto hook
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'] ?? [] as $key => $func) {
                $params = [
                    'pageRow' => $pageRow,
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
     * Creates a list of URLs from input array (and submits them to queue if asked for)
     * See Web > Info module script + "indexed_search"'s crawler hook-client using this!
     *
     * @param array $vv Information about URLs from pageRow to crawl.
     * @param array $pageRow Page row
     * @param int $scheduledTime Unix time to schedule indexing to, typically time()
     * @param int $reqMinute Number of requests per minute (creates the interleave between requests)
     * @param bool $submitCrawlUrls If set, submits the URLs to queue
     * @param bool $downloadCrawlUrls If set (and submitcrawlUrls is false) will fill $downloadUrls with entries)
     * @param array $duplicateTrack Array which is passed by reference and contains the an id per url to secure we will not crawl duplicates
     * @param array $downloadUrls Array which will be filled with URLS for download if flag is set.
     * @param array $incomingProcInstructions Array of processing instructions
     * @return string List of URLs (meant for display in backend module)
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
        if (! is_array($vv['URLs'])) {
            return 'ERROR - no URL generated';
        }
        $urlLog = [];
        $pageId = (int) $pageRow['uid'];
        $configurationHash = $this->getConfigurationHash($vv);
        $skipInnerCheck = $this->queueRepository->noUnprocessedQueueEntriesForPageWithConfigurationHashExist($pageId, $configurationHash);

        foreach ($vv['URLs'] as $urlQuery) {
            if (! $this->drawURLs_PIfilter($vv['subCfg']['procInstrFilter'], $incomingProcInstructions)) {
                continue;
            }
            $url = (string) $this->getUrlFromPageAndQueryParameters(
                $pageId,
                $urlQuery,
                $vv['subCfg']['baseUrl'] ?? null,
                $vv['subCfg']['force_ssl'] ?? 0
            );

            // Create key by which to determine unique-ness:
            $uKey = $url . '|' . $vv['subCfg']['userGroups'] . '|' . $vv['subCfg']['procInstrFilter'];

            if (isset($duplicateTrack[$uKey])) {
                //if the url key is registered just display it and do not resubmit is
                $urlLog[] = '<em><span class="text-muted">' . htmlspecialchars($url) . '</span></em>';
            } else {
                // Scheduled time:
                $schTime = $scheduledTime + round(count($duplicateTrack) * (60 / $reqMinute));
                $schTime = intval($schTime / 60) * 60;
                $formattedDate = BackendUtility::datetime($schTime);
                $this->urlList[] = '[' . $formattedDate . '] ' . $url;
                $urlList = '[' . $formattedDate . '] ' . htmlspecialchars($url);

                // Submit for crawling!
                if ($submitCrawlUrls) {
                    $added = $this->addUrl(
                        $pageId,
                        $url,
                        $vv['subCfg'],
                        $scheduledTime,
                        $configurationHash,
                        $skipInnerCheck
                    );
                    if ($added === false) {
                        $urlList .= ' (URL already existed)';
                    }
                } elseif ($downloadCrawlUrls) {
                    $downloadUrls[$url] = $url;
                }
                $urlLog[] = $urlList;
            }
            $duplicateTrack[$uKey] = true;
        }

        return implode('<br>', $urlLog);
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

    public function getPageTSconfigForId($id): array
    {
        if (! $this->MP) {
            $pageTSconfig = BackendUtility::getPagesTSconfig($id);
        } else {
            // TODO: Please check, this makes no sense to split a boolean value.
            [, $mountPointId] = explode('-', $this->MP);
            $pageTSconfig = BackendUtility::getPagesTSconfig($mountPointId);
        }

        // Call a hook to alter configuration
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'])) {
            $params = [
                'pageId' => $id,
                'pageTSConfig' => &$pageTSconfig,
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['getPageTSconfigForId'] as $userFunc) {
                GeneralUtility::callUserFunction($userFunc, $params, $this);
            }
        }
        return $pageTSconfig;
    }

    /**
     * This methods returns an array of configurations.
     * Adds no urls!
     */
    public function getUrlsForPageId(int $pageId): array
    {
        // Get page TSconfig for page ID
        $pageTSconfig = $this->getPageTSconfigForId($pageId);

        $res = [];

        // Fetch Crawler Configuration from pageTSconfig
        $crawlerCfg = $pageTSconfig['tx_crawler.']['crawlerCfg.']['paramSets.'] ?? [];
        foreach ($crawlerCfg as $key => $values) {
            if (! is_array($values)) {
                continue;
            }
            $key = str_replace('.', '', $key);
            // Sub configuration for a single configuration string:
            $subCfg = (array) $crawlerCfg[$key . '.'];
            $subCfg['key'] = $key;

            if (strcmp($subCfg['procInstrFilter'], '')) {
                $subCfg['procInstrFilter'] = implode(',', GeneralUtility::trimExplode(',', $subCfg['procInstrFilter']));
            }
            $pidOnlyList = implode(',', GeneralUtility::trimExplode(',', $subCfg['pidsOnly'], true));

            // process configuration if it is not page-specific or if the specific page is the current page:
            // TODO: Check if $pidOnlyList can be kept as Array instead of imploded
            if (! strcmp((string) $subCfg['pidsOnly'], '') || GeneralUtility::inList($pidOnlyList, strval($pageId))) {

                // Explode, process etc.:
                $res[$key] = [];
                $res[$key]['subCfg'] = $subCfg;
                $res[$key]['paramParsed'] = GeneralUtility::explodeUrl2Array($crawlerCfg[$key]);
                $res[$key]['paramExpanded'] = $this->expandParameters($res[$key]['paramParsed'], $pageId);
                $res[$key]['origin'] = 'pagets';

                // recognize MP value
                if (! $this->MP) {
                    $res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], ['?id=' . $pageId]);
                } else {
                    $res[$key]['URLs'] = $this->compileUrls($res[$key]['paramExpanded'], ['?id=' . $pageId . '&MP=' . $this->MP]);
                }
            }
        }

        // Get configuration from tx_crawler_configuration records up the rootline
        $crawlerConfigurations = $this->configurationRepository->getCrawlerConfigurationRecordsFromRootLine($pageId);
        foreach ($crawlerConfigurations as $configurationRecord) {

            // check access to the configuration record
            if (empty($configurationRecord['begroups']) || $this->getBackendUser()->isAdmin() || $this->hasGroupAccess($this->getBackendUser()->user['usergroup_cached_list'], $configurationRecord['begroups'])) {
                $pidOnlyList = implode(',', GeneralUtility::trimExplode(',', $configurationRecord['pidsonly'], true));

                // process configuration if it is not page-specific or if the specific page is the current page:
                // TODO: Check if $pidOnlyList can be kept as Array instead of imploded
                if (! strcmp($configurationRecord['pidsonly'], '') || GeneralUtility::inList($pidOnlyList, strval($pageId))) {
                    $key = $configurationRecord['name'];

                    // don't overwrite previously defined paramSets
                    if (! isset($res[$key])) {

                        /* @var $TSparserObject \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
                        $TSparserObject = GeneralUtility::makeInstance(TypoScriptParser::class);
                        $TSparserObject->parse($configurationRecord['processing_instruction_parameters_ts']);

                        $subCfg = [
                            'procInstrFilter' => $configurationRecord['processing_instruction_filter'],
                            'procInstrParams.' => $TSparserObject->setup,
                            'baseUrl' => $configurationRecord['base_url'],
                            'force_ssl' => (int) $configurationRecord['force_ssl'],
                            'userGroups' => $configurationRecord['fegroups'],
                            'exclude' => $configurationRecord['exclude'],
                            'key' => $key,
                        ];

                        if (! in_array($pageId, $this->expandExcludeString($subCfg['exclude']), true)) {
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
     * Find all configurations of subpages of a page
     * TODO: Write Functional Tests
     */
    public function getConfigurationsForBranch(int $rootid, int $depth): array
    {
        $configurationsForBranch = [];
        $pageTSconfig = $this->getPageTSconfigForId($rootid);
        $sets = $pageTSconfig['tx_crawler.']['crawlerCfg.']['paramSets.'] ?? [];
        foreach ($sets as $key => $value) {
            if (! is_array($value)) {
                continue;
            }
            $configurationsForBranch[] = substr($key, -1) === '.' ? substr($key, 0, -1) : $key;
        }
        $pids = [];
        $rootLine = BackendUtility::BEgetRootLine($rootid);
        foreach ($rootLine as $node) {
            $pids[] = $node['uid'];
        }
        /* @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $tree->init(empty($perms_clause) ? '' : ('AND ' . $perms_clause));
        $tree->getTree($rootid, $depth, '');
        foreach ($tree->tree as $node) {
            $pids[] = $node['row']['uid'];
        }

        $queryBuilder = $this->getQueryBuilder('tx_crawler_configuration');
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
     * Check if a user has access to an item
     * (e.g. get the group list of the current logged in user from $GLOBALS['TSFE']->gr_list)
     *
     * @param string $groupList Comma-separated list of (fe_)group UIDs from a user
     * @param string $accessList Comma-separated list of (fe_)group UIDs of the item to access
     * @return bool                 TRUE if at least one of the users group UIDs is in the access list or the access list is empty
     * @see \TYPO3\CMS\Frontend\Page\PageRepository::getMultipleGroupsWhereClause()
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
            if (strpos($v, '[') === 0 && substr($v, -1) === ']') {
                // So, find the value inside brackets and reset the paramArray value as an array.
                $v = substr($v, 1, -1);
                $paramArray[$p] = [];

                // Explode parts and traverse them:
                $parts = explode('|', $v);
                foreach ($parts as $pV) {

                    // Look for integer range: (fx. 1-34 or -40--30 // reads minus 40 to minus 30)
                    if (preg_match('/^(-?[0-9]+)\s*-\s*(-?[0-9]+)$/', trim($pV), $reg)) {
                        $reg = $this->swapIfFirstIsLargerThanSecond($reg);

                        // Traverse range, add values:
                        $runAwayBrake = 1000; // Limit to size of range!
                        for ($a = $reg[1]; $a <= $reg[2]; $a++) {
                            $paramArray[$p][] = $a;
                            $runAwayBrake--;
                            if ($runAwayBrake <= 0) {
                                break;
                            }
                        }
                    } elseif (strpos(trim($pV), '_TABLE:') === 0) {

                        // Parse parameters:
                        $subparts = GeneralUtility::trimExplode(';', $pV);
                        $subpartParams = [];
                        foreach ($subparts as $spV) {
                            [$pKey, $pVal] = GeneralUtility::trimExplode(':', $spV);
                            $subpartParams[$pKey] = $pVal;
                        }

                        // Table exists:
                        if (isset($GLOBALS['TCA'][$subpartParams['_TABLE']])) {
                            $lookUpPid = isset($subpartParams['_PID']) ? intval($subpartParams['_PID']) : intval($pid);
                            $recursiveDepth = isset($subpartParams['_RECURSIVE']) ? intval($subpartParams['_RECURSIVE']) : 0;
                            $pidField = isset($subpartParams['_PIDFIELD']) ? trim($subpartParams['_PIDFIELD']) : 'pid';
                            $where = $subpartParams['_WHERE'] ?? '';
                            $addTable = $subpartParams['_ADDTABLE'] ?? '';

                            $fieldName = $subpartParams['_FIELD'] ? $subpartParams['_FIELD'] : 'uid';
                            if ($fieldName === 'uid' || $GLOBALS['TCA'][$subpartParams['_TABLE']]['columns'][$fieldName]) {
                                $queryBuilder = $this->getQueryBuilder($subpartParams['_TABLE']);

                                if ($recursiveDepth > 0) {
                                    /** @var \TYPO3\CMS\Core\Database\QueryGenerator $queryGenerator */
                                    $queryGenerator = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\QueryGenerator::class);
                                    $pidList = $queryGenerator->getTreeList($lookUpPid, $recursiveDepth, 0, 1);
                                    $pidArray = GeneralUtility::intExplode(',', $pidList);
                                } else {
                                    $pidArray = [(string) $lookUpPid];
                                }

                                $queryBuilder->getRestrictions()
                                    ->removeAll()
                                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

                                $queryBuilder
                                    ->select($fieldName)
                                    ->from($subpartParams['_TABLE'])
                                    ->where(
                                        $queryBuilder->expr()->in($pidField, $queryBuilder->createNamedParameter($pidArray, Connection::PARAM_INT_ARRAY)),
                                        $where
                                    );

                                if (! empty($addTable)) {
                                    // TODO: Check if this works as intended!
                                    $queryBuilder->add('from', $addTable);
                                }
                                $transOrigPointerField = $GLOBALS['TCA'][$subpartParams['_TABLE']]['ctrl']['transOrigPointerField'];

                                if ($subpartParams['_ENABLELANG'] && $transOrigPointerField) {
                                    $queryBuilder->andWhere(
                                        $queryBuilder->expr()->lte(
                                            $transOrigPointerField,
                                            0
                                        )
                                    );
                                }

                                $statement = $queryBuilder->execute();

                                $rows = [];
                                while ($row = $statement->fetch()) {
                                    $rows[$row[$fieldName]] = $row;
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
                            'pid' => $pid,
                        ];
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'] as $_funcRef) {
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
                $newUrls[] = $url . (strcmp((string) $val, '') ? '&' . rawurlencode($varName) . '=' . rawurlencode((string) $val) : '');

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
     * @param string $filter Filter: "all" => all entries, "pending" => all that is not yet run, "finished" => all complete ones
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
        switch ($filter) {
            case 'pending':
                $queryBuilder->andWhere($queryBuilder->expr()->eq('exec_time', 0));
                break;
            case 'finished':
                $queryBuilder->andWhere($queryBuilder->expr()->gt('exec_time', 0));
                break;
        }

        if ($doFlush) {
            if ($doFullFlush) {
                $this->queueRepository->flushQueue('all');
            } else {
                $this->queueRepository->flushQueue($filter);
            }
        }
        if ($itemsPerPage > 0) {
            $queryBuilder
                ->setMaxResults((int) $itemsPerPage);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Return array of records from crawler queue for input set ID
     *
     * @param int $set_id Set ID for which to look up log entries.
     * @param string $filter Filter: "all" => all entries, "pending" => all that is not yet run, "finished" => all complete ones
     * @param bool $doFlush If TRUE, then entries selected at DELETED(!) instead of selected!
     * @param int $itemsPerPage Limit the amount of entries per page default is 10
     * @return array
     *
     * @deprecated
     */
    public function getLogEntriesForSetId(int $set_id, string $filter = '', bool $doFlush = false, bool $doFullFlush = false, int $itemsPerPage = 10)
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
        if ($doFlush) {
            $addWhere = $query->add($expressionBuilder->eq('set_id', (int) $set_id));
            $this->flushQueue($doFullFlush ? '' : $addWhere);
            return [];
        }
        if ($itemsPerPage > 0) {
            $queryBuilder
                ->setMaxResults((int) $itemsPerPage);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Adding call back entries to log (called from hooks typically, see indexed search class "class.crawler.php"
     *
     * @param integer $setId Set ID
     * @param array $params Parameters to pass to call back function
     * @param string $callBack Call back object reference, eg. 'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_crawler'
     * @param integer $page_id Page ID to attach it to
     * @param integer $schedule Time at which to activate
     */
    public function addQueueEntry_callBack($setId, $params, $callBack, $page_id = 0, $schedule = 0): void
    {
        if (! is_array($params)) {
            $params = [];
        }
        $params['_CALLBACKOBJ'] = $callBack;

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_queue')
            ->insert(
                'tx_crawler_queue',
                [
                    'page_id' => (int) $page_id,
                    'parameters' => serialize($params),
                    'scheduled' => (int) $schedule ?: $this->getCurrentTime(),
                    'exec_time' => 0,
                    'set_id' => (int) $setId,
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
            'url' => $url,
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

        // Compile value array:
        $parameters_serialized = serialize($parameters);
        $fieldArray = [
            'page_id' => (int) $id,
            'parameters' => $parameters_serialized,
            'parameters_hash' => GeneralUtility::shortMD5($parameters_serialized),
            'configuration_hash' => $configurationHash,
            'scheduled' => $tstamp,
            'exec_time' => 0,
            'set_id' => (int) $this->setID,
            'result_data' => '',
            'configuration' => $subCfg['key'],
        ];

        if ($this->registerQueueEntriesInternallyOnly) {
            //the entries will only be registered and not stored to the database
            $this->queueEntries[] = $fieldArray;
        } else {
            if (! $skipInnerDuplicationCheck) {
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

                $signalPayload = ['uid' => $uid, 'fieldArray' => $fieldArray];
                SignalSlotUtility::emitSignal(
                    self::class,
                    SignalSlotUtility::SIGNAL_URL_ADDED_TO_QUEUE,
                    $signalPayload
                );
            } else {
                $signalPayload = ['rows' => $rows, 'fieldArray' => $fieldArray];
                SignalSlotUtility::emitSignal(
                    self::class,
                    SignalSlotUtility::SIGNAL_DUPLICATE_URL_IN_QUEUE,
                    $signalPayload
                );
            }
        }

        return $urlAdded;
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
        if (! $force) {
            $queryBuilder
                ->andWhere('exec_time = 0')
                ->andWhere('process_scheduled > 0');
        }
        $queueRec = $queryBuilder->execute()->fetch();

        if (! is_array($queueRec)) {
            return;
        }

        SignalSlotUtility::emitSignal(
            self::class,
            SignalSlotUtility::SIGNAL_QUEUEITEM_PREPROCESS,
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
                ['qid' => (int) $queueId]
            );

        $result = $this->queueExecutor->executeQueueItem($queueRec, $this);
        if ($result['content'] === null) {
            $resultData = 'An errors happened';
        } else {
            /** @var JsonCompatibilityConverter $jsonCompatibilityConverter */
            $jsonCompatibilityConverter = GeneralUtility::makeInstance(JsonCompatibilityConverter::class);
            $resultData = $jsonCompatibilityConverter->convert($result['content']);
        }

        //atm there's no need to point to specific pollable extensions
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'] as $pollable) {
                // only check the success value if the instruction is runnig
                // it is important to name the pollSuccess key same as the procInstructions key
                if (is_array($resultData['parameters']['procInstructions'])
                    && in_array(
                        $pollable,
                        $resultData['parameters']['procInstructions'], true
                    )
                ) {
                    if (! empty($resultData['success'][$pollable]) && $resultData['success'][$pollable]) {
                        $ret |= self::CLI_STATUS_POLLABLE_PROCESSED;
                    }
                }
            }
        }

        // Set result in log which also denotes the end of the processing of this entry.
        $field_array = ['result_data' => serialize($result)];

        SignalSlotUtility::emitSignal(
            self::class,
            SignalSlotUtility::SIGNAL_QUEUEITEM_POSTPROCESS,
            [$queueId, &$field_array]
        );

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_queue')
            ->update(
                'tx_crawler_queue',
                $field_array,
                ['qid' => (int) $queueId]
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
        $connectionForCrawlerQueue = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
        $connectionForCrawlerQueue->insert(
            $this->tableName,
            $field_array
        );
        $queueId = $field_array['qid'] = $connectionForCrawlerQueue->lastInsertId($this->tableName, 'qid');

        $result = $this->queueExecutor->executeQueueItem($field_array, $this);

        // Set result in log which also denotes the end of the processing of this entry.
        $field_array = ['result_data' => serialize($result)];

        SignalSlotUtility::emitSignal(
            self::class,
            SignalSlotUtility::SIGNAL_QUEUEITEM_POSTPROCESS,
            [$queueId, &$field_array]
        );

        $connectionForCrawlerQueue->update(
            $this->tableName,
            $field_array,
            ['qid' => $queueId]
        );

        return $result;
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
        $perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $tree->init('AND ' . $perms_clause);

        $pageInfo = BackendUtility::readPageAccess($id, $perms_clause);
        if (is_array($pageInfo)) {
            // Set root row:
            $tree->tree[] = [
                'row' => $pageInfo,
                'HTML' => $this->iconFactory->getIconForRecord('pages', $pageInfo, Icon::SIZE_SMALL),
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
            if ($data['row']['doktype'] === PageRepository::DOKTYPE_MOUNTPOINT) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $mountpage = $queryBuilder
                    ->select('*')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($data['row']['uid'], \PDO::PARAM_INT))
                    )
                    ->execute()
                    ->fetchAll();
                $queryBuilder->resetRestrictions();

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

            if (! empty($excludeString)) {
                /** @var PageTreeView $tree */
                $tree = GeneralUtility::makeInstance(PageTreeView::class);
                $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));

                $excludeParts = GeneralUtility::trimExplode(',', $excludeString);

                foreach ($excludeParts as $excludePart) {
                    [$pid, $depth] = GeneralUtility::trimExplode('+', $excludePart);

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
     */
    public function drawURLs_addRowsForPage(array $pageRow, string $pageTitle): string
    {
        $skipMessage = '';

        // Get list of configurations
        $configurations = $this->getUrlsForPageRow($pageRow, $skipMessage);

        if (! empty($this->incomingConfigurationSelection)) {
            // remove configuration that does not match the current selection
            foreach ($configurations as $confKey => $confArray) {
                if (! in_array($confKey, $this->incomingConfigurationSelection, true)) {
                    unset($configurations[$confKey]);
                }
            }
        }

        // Traverse parameter combinations:
        $c = 0;
        $content = '';
        if (! empty($configurations)) {
            foreach ($configurations as $confKey => $confArray) {

                // Title column:
                if (! $c) {
                    $titleClm = '<td rowspan="' . count($configurations) . '">' . $pageTitle . '</td>';
                } else {
                    $titleClm = '';
                }

                if (! in_array($pageRow['uid'], $this->expandExcludeString($confArray['subCfg']['exclude']), true)) {

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
                                <td>' . htmlspecialchars('&' . $gVar . '=') . '<br/>' .
                            '(' . count($gVal) . ')' .
                            '</td>
                                <td nowrap="nowrap">' . nl2br(htmlspecialchars(implode(chr(10), $gVal))) . '</td>
                            </tr>
                        ';
                        $calcRes *= count($gVal);
                        $calcAccu[] = count($gVal);
                    }
                    $paramExpanded = '<table>' . $paramExpanded . '</table>';
                    $paramExpanded .= 'Comb: ' . implode('*', $calcAccu) . '=' . $calcRes;

                    // Options
                    $optionValues = '';
                    if ($confArray['subCfg']['userGroups']) {
                        $optionValues .= 'User Groups: ' . $confArray['subCfg']['userGroups'] . '<br/>';
                    }
                    if ($confArray['subCfg']['procInstrFilter']) {
                        $optionValues .= 'ProcInstr: ' . $confArray['subCfg']['procInstrFilter'] . '<br/>';
                    }

                    // Compile row:
                    $content .= '
                        <tr>
                            ' . $titleClm . '
                            <td>' . htmlspecialchars($confKey) . '</td>
                            <td>' . nl2br(htmlspecialchars(rawurldecode(trim(str_replace('&', chr(10) . '&', GeneralUtility::implodeArrayForUrl('', $confArray['paramParsed'])))))) . '</td>
                            <td>' . $paramExpanded . '</td>
                            <td nowrap="nowrap">' . $urlList . '</td>
                            <td nowrap="nowrap">' . $optionValues . '</td>
                            <td nowrap="nowrap">' . DebugUtility::viewArray($confArray['subCfg']['procInstrParams.']) . '</td>
                        </tr>';
                } else {
                    $content .= '<tr>
                            ' . $titleClm . '
                            <td>' . htmlspecialchars($confKey) . '</td>
                            <td colspan="5"><em>No entries</em> (Page is excluded in this configuration)</td>
                        </tr>';
                }

                $c++;
            }
        } else {
            $message = ! empty($skipMessage) ? ' (' . $skipMessage . ')' : '';

            // Compile row:
            $content .= '
                <tr>
                    <td>' . $pageTitle . '</td>
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
     */
    public function CLI_run(int $countInARun, int $sleepTime, int $sleepAfterFinish): int
    {
        $result = 0;
        $counter = 0;

        // First, run hooks:
        $this->CLI_runHooks();

        // Clean up the queue
        $this->queueRepository->cleanupQueue();

        // Select entries:
        $rows = $this->queueRepository->fetchRecordsToBeCrawled($countInARun);

        if (! empty($rows)) {
            $quidList = [];

            foreach ($rows as $r) {
                $quidList[] = $r['qid'];
            }

            $processId = $this->CLI_buildProcessId();

            //save the number of assigned queue entries to determine how many have been processed later
            $numberOfAffectedRows = $this->queueRepository->updateProcessIdAndSchedulerForQueueIds($quidList, $processId);
            $this->processRepository->updateProcessAssignItemsCount($numberOfAffectedRows, $processId);

            if ($numberOfAffectedRows !== count($quidList)) {
                $this->CLI_debug('Nothing processed due to multi-process collision (' . $this->CLI_buildProcessId() . ')');
                return ($result | self::CLI_STATUS_ABORTED);
            }

            foreach ($rows as $r) {
                $result |= $this->readUrl($r['qid']);

                $counter++;
                usleep((int) $sleepTime); // Just to relax the system

                // if during the start and the current read url the cli has been disable we need to return from the function
                // mark the process NOT as ended.
                if ($this->getDisabled()) {
                    return ($result | self::CLI_STATUS_ABORTED);
                }

                if (! $this->processRepository->isProcessActive($this->CLI_buildProcessId())) {
                    $this->CLI_debug('conflict / timeout (' . $this->CLI_buildProcessId() . ')');
                    $result |= self::CLI_STATUS_ABORTED;
                    break; //possible timeout
                }
            }

            sleep((int) $sleepAfterFinish);

            $msg = 'Rows: ' . $counter;
            $this->CLI_debug($msg . ' (' . $this->CLI_buildProcessId() . ')');
        } else {
            $this->CLI_debug('Nothing within queue which needs to be processed (' . $this->CLI_buildProcessId() . ')');
        }

        if ($counter > 0) {
            $result |= self::CLI_STATUS_PROCESSED;
        }

        return $result;
    }

    /**
     * Activate hooks
     */
    public function CLI_runHooks(): void
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
     * @param string $id identification string for the process
     * @return boolean
     * @todo preemption might not be the most elegant way to clean up
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
        if ($processCount < (int) $this->extensionSettings['processLimit']) {
            $this->CLI_debug('add process ' . $this->CLI_buildProcessId() . ' (' . ($processCount + 1) . '/' . (int) $this->extensionSettings['processLimit'] . ')');

            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_crawler_process')->insert(
                'tx_crawler_process',
                [
                    'process_id' => $id,
                    'active' => 1,
                    'ttl' => $currentTime + (int) $this->extensionSettings['processMaxRunTime'],
                    'system_process_id' => $systemProcessId,
                ]
            );
        } else {
            $this->CLI_debug('Processlimit reached (' . ($processCount) . '/' . (int) $this->extensionSettings['processLimit'] . ')');
            $ret = false;
        }

        $this->processRepository->deleteProcessesMarkedAsDeleted();
        $this->CLI_releaseProcesses($orphanProcesses);

        return $ret;
    }

    /**
     * Release a process and the required resources
     *
     * @param mixed $releaseIds string with a single process-id or array with multiple process-ids
     * @return boolean
     */
    public function CLI_releaseProcesses($releaseIds)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        if (! is_array($releaseIds)) {
            $releaseIds = [$releaseIds];
        }

        if (empty($releaseIds)) {
            return false;   //nothing to release
        }

        // some kind of 2nd chance algo - this way you need at least 2 processes to have a real cleanup
        // this ensures that a single process can't mess up the entire process table

        // mark all processes as deleted which have no "waiting" queue-entires and which are not active

        $queryBuilder
            ->update($this->tableName, 'q')
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

        $this->processRepository->markRequestedProcessesAsNotActive($releaseIds);
        $this->queueRepository->unsetProcessScheduledAndProcessIdForQueueEntries($releaseIds);

        return true;
    }

    /**
     * Create a unique Id for the current process
     *
     * @return string  the ID
     */
    public function CLI_buildProcessId()
    {
        if (! $this->processID) {
            $this->processID = GeneralUtility::shortMD5(microtime(true));
        }
        return $this->processID;
    }

    /**
     * Prints a message to the stdout (only if debug-mode is enabled)
     *
     * @param string $msg the message
     */
    public function CLI_debug($msg): void
    {
        if ((int) $this->extensionSettings['processDebug']) {
            echo $msg . "\n";
            flush();
        }
    }

    /**
     * Cleans up entries that stayed for too long in the queue. These are:
     * - processed entries that are over 1.5 days in age
     * - scheduled entries that are over 7 days old
     *
     * @deprecated
     */
    public function cleanUpOldQueueEntries(): void
    {
        $processedAgeInSeconds = $this->extensionSettings['cleanUpProcessedAge'] * 86400; // 24*60*60 Seconds in 24 hours
        $scheduledAgeInSeconds = $this->extensionSettings['cleanUpScheduledAge'] * 86400;

        $now = time();
        $condition = '(exec_time<>0 AND exec_time<' . ($now - $processedAgeInSeconds) . ') OR scheduled<=' . ($now - $scheduledAgeInSeconds);
        $this->flushQueue($condition);
    }

    /**
     * Removes queue entries
     *
     * @param string $where SQL related filter for the entries which should be removed
     *
     * @deprecated
     */
    protected function flushQueue($where = ''): void
    {
        $realWhere = strlen((string) $where) > 0 ? $where : '1=1';

        $queryBuilder = $this->getQueryBuilder($this->tableName);

        $groups = $queryBuilder
            ->selectLiteral('DISTINCT set_id')
            ->from($this->tableName)
            ->where($realWhere)
            ->execute()
            ->fetchAll();
        if (is_array($groups)) {
            foreach ($groups as $group) {
                $subSet = $queryBuilder
                    ->select('qid', 'set_id')
                    ->from($this->tableName)
                    ->where(
                        $realWhere,
                        $queryBuilder->expr()->eq('set_id', $group['set_id'])
                    )
                    ->execute()
                    ->fetchAll();

                $payLoad = ['subSet' => $subSet];
                SignalSlotUtility::emitSignal(
                    self::class,
                    SignalSlotUtility::SIGNAL_QUEUE_ENTRY_FLUSH,
                    $payLoad
                );
            }
        }

        $queryBuilder
            ->delete($this->tableName)
            ->where($realWhere)
            ->execute();
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

        $queryBuilder
            ->andWhere('NOT exec_time')
            ->andWhere('NOT process_id')
            ->andWhere($queryBuilder->expr()->eq('page_id', $queryBuilder->createNamedParameter($fieldArray['page_id'], \PDO::PARAM_INT)))
            ->andWhere($queryBuilder->expr()->eq('parameters_hash', $queryBuilder->createNamedParameter($fieldArray['parameters_hash'], \PDO::PARAM_STR)));

        $statement = $queryBuilder->execute();

        while ($row = $statement->fetch()) {
            $rows[] = $row['qid'];
        }

        return $rows;
    }

    /**
     * Returns a md5 hash generated from a serialized configuration array.
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
     * Build a URL from a Page and the Query String. If the page has a Site configuration, it can be built by using
     * the Site instance.
     *
     * @param int $httpsOrHttp see tx_crawler_configuration.force_ssl
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     * @throws \TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException
     */
    protected function getUrlFromPageAndQueryParameters(int $pageId, string $queryString, ?string $alternativeBaseUrl, int $httpsOrHttp): UriInterface
    {
        $site = GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId((int) $pageId);
        if ($site instanceof Site) {
            $queryString = ltrim($queryString, '?&');
            $queryParts = [];
            parse_str($queryString, $queryParts);
            unset($queryParts['id']);
            // workaround as long as we don't have native language support in crawler configurations
            if (isset($queryParts['L'])) {
                $queryParts['_language'] = $queryParts['L'];
                unset($queryParts['L']);
                $siteLanguage = $site->getLanguageById((int) $queryParts['_language']);
            } else {
                $siteLanguage = $site->getDefaultLanguage();
            }
            $url = $site->getRouter()->generateUri($pageId, $queryParts);
            if (! empty($alternativeBaseUrl)) {
                $alternativeBaseUrl = new Uri($alternativeBaseUrl);
                $url = $url->withHost($alternativeBaseUrl->getHost());
                $url = $url->withScheme($alternativeBaseUrl->getScheme());
                $url = $url->withPort($alternativeBaseUrl->getPort());
                if ($userInfo = $alternativeBaseUrl->getUserInfo()) {
                    $url = $url->withUserInfo($userInfo);
                }
            }
        } else {
            // Technically this is not possible with site handling, but kept for backwards-compatibility reasons
            // Once EXT:crawler is v10-only compatible, this should be removed completely
            $baseUrl = ($alternativeBaseUrl ?: GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
            $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
            $queryString .= '&cHash=' . $cacheHashCalculator->generateForParameters($queryString);
            $url = rtrim($baseUrl, '/') . '/index.php' . $queryString;
            $url = new Uri($url);
        }

        if ($httpsOrHttp === -1) {
            $url = $url->withScheme('http');
        } elseif ($httpsOrHttp === 1) {
            $url = $url->withScheme('https');
        }

        return $url;
    }

    protected function swapIfFirstIsLargerThanSecond(array $reg): array
    {
        // Swap if first is larger than last:
        if ($reg[1] > $reg[2]) {
            $temp = $reg[2];
            $reg[2] = $reg[1];
            $reg[1] = $temp;
        }

        return $reg;
    }

    /**
     * @return BackendUserAuthentication
     */
    private function getBackendUser()
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        if ($this->backendUser === null) {
            $this->backendUser = $GLOBALS['BE_USER'];
        }
        return $this->backendUser;
    }

    /**
     * Get querybuilder for given table
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getQueryBuilder(string $table)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }
}
