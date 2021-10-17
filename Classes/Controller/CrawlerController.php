<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Crawler;
use AOE\Crawler\CrawlStrategy\CrawlStrategyFactory;
use AOE\Crawler\Domain\Repository\ConfigurationRepository;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\QueueExecutor;
use AOE\Crawler\Service\ConfigurationService;
use AOE\Crawler\Service\PageService;
use AOE\Crawler\Service\UrlService;
use AOE\Crawler\Utility\SignalSlotUtility;
use AOE\Crawler\Value\QueueRow;
use PDO;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class CrawlerController
 *
 * @package AOE\Crawler\Controller
 */
class CrawlerController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var UrlService
     */
    private $urlService;

    /************************************
     *
     * Getting URLs based on Page TSconfig
     *
     ************************************/

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $crawlStrategyFactory = GeneralUtility::makeInstance(CrawlStrategyFactory::class);
        $this->queueRepository = $objectManager->get(QueueRepository::class);
        $this->processRepository = $objectManager->get(ProcessRepository::class);
        $this->configurationRepository = $objectManager->get(ConfigurationRepository::class);
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $this->queueExecutor = GeneralUtility::makeInstance(QueueExecutor::class, $crawlStrategyFactory);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->crawler = GeneralUtility::makeInstance(Crawler::class);
        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $this->urlService = GeneralUtility::makeInstance(UrlService::class);

        /** @var ExtensionConfigurationProvider $configurationProvider */
        $configurationProvider = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class);
        $settings = $configurationProvider->getExtensionConfiguration();
        $this->extensionSettings = is_array($settings) ? $settings : [];

        if (MathUtility::convertToPositiveInteger($this->extensionSettings['countInARun']) === 0) {
            $this->extensionSettings['countInARun'] = 100;
        }

        $this->extensionSettings['processLimit'] = MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1);
        $this->setMaximumUrlsToCompile(MathUtility::forceIntegerInRange($this->extensionSettings['maxCompileUrls'], 1, 1000000000, 10000));
    }

    public function setMaximumUrlsToCompile(int $maximumUrlsToCompile): void
    {
        $this->maximumUrlsToCompile = $maximumUrlsToCompile;
    }

    /**
     * Sets the extensions settings (unserialized pendant of $TYPO3_CONF_VARS['EXT']['extConf']['crawler']).
     */
    public function setExtensionSettings(array $extensionSettings): void
    {
        $this->extensionSettings = $extensionSettings;
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
        if (! is_int($pageRow['uid'])) {
            $skipMessage = 'PageUid ' . $pageRow['uid'] . ' was not an integer';
            return [];
        }

        $message = $this->getPageService()->checkIfPageShouldBeSkipped($pageRow);
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

        $urlService = new UrlService();

        foreach ($vv['URLs'] as $urlQuery) {
            if (! $this->drawURLs_PIfilter($vv['subCfg']['procInstrFilter'], $incomingProcInstructions)) {
                continue;
            }
            $url = (string) $urlService->getUrlFromPageAndQueryParameters(
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

        // Todo: Find a better option to have this correct in both backend (<br>) and cli (<new line>)
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

    public function getPageTSconfigForId(int $id): array
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

        $mountPoint = is_string($this->MP) ? $this->MP : '';

        $res = [];

        // Fetch Crawler Configuration from pageTSConfig
        $res = $this->configurationService->getConfigurationFromPageTS($pageTSconfig, $pageId, $res, $mountPoint);

        // Get configuration from tx_crawler_configuration records up the rootline
        $res = $this->configurationService->getConfigurationFromDatabase($pageId, $res);

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

        $configurations = $this->configurationRepository->getCrawlerConfigurationRecordsFromRootLine($rootid, $pids);

        foreach ($configurations as $configuration) {
            $configurationsForBranch[] = $configuration['name'];
        }
        return $configurationsForBranch;
    }

    /************************************
     *
     * Crawler log
     *
     ************************************/

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

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(QueueRepository::TABLE_NAME)
            ->insert(
                QueueRepository::TABLE_NAME,
                [
                    'page_id' => (int) $page_id,
                    'parameters' => json_encode($params),
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
        $parameters_serialized = json_encode($parameters);
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
                $rows = $this->queueRepository->getDuplicateQueueItemsIfExists(
                    (bool) $this->extensionSettings['enableTimeslot'],
                    $tstamp,
                    $this->getCurrentTime(),
                    $fieldArray['page_id'],
                    $fieldArray['parameters_hash']
                );
            }

            if (empty($rows)) {
                $connectionForCrawlerQueue = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(QueueRepository::TABLE_NAME);
                $connectionForCrawlerQueue->insert(
                    QueueRepository::TABLE_NAME,
                    $fieldArray
                );
                $uid = $connectionForCrawlerQueue->lastInsertId(QueueRepository::TABLE_NAME, 'qid');
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
     *
     * @return int|null
     */
    public function readUrl($queueId, $force = false, string $processId = '')
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(QueueRepository::TABLE_NAME);
        $ret = 0;
        $this->logger->debug('crawler-readurl start ' . microtime(true));

        $queryBuilder
            ->select('*')
            ->from(QueueRepository::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('qid', $queryBuilder->createNamedParameter($queueId, PDO::PARAM_INT))
            );
        if (! $force) {
            $queryBuilder
                ->andWhere('exec_time = 0')
                ->andWhere('process_scheduled > 0');
        }
        $queueRec = $queryBuilder->execute()->fetchAssociative();

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

        if (! empty($processId)) {
            //if mulitprocessing is used we need to store the id of the process which has handled this entry
            $field_array['process_id_completed'] = $processId;
        }

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(QueueRepository::TABLE_NAME)
            ->update(
                QueueRepository::TABLE_NAME,
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

            //atm there's no need to point to specific pollable extensions
            if (is_array($resultData) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'] as $pollable) {
                    // only check the success value if the instruction is runnig
                    // it is important to name the pollSuccess key same as the procInstructions key
                    if (is_array($resultData['parameters']['procInstructions'])
                        && in_array(
                            $pollable,
                            $resultData['parameters']['procInstructions'], true
                        )
                    ) {
                        if (! empty($resultData['success'][$pollable])) {
                            $ret |= self::CLI_STATUS_POLLABLE_PROCESSED;
                        }
                    }
                }
            }
        }
        // Set result in log which also denotes the end of the processing of this entry.
        $field_array = ['result_data' => json_encode($result)];

        SignalSlotUtility::emitSignal(
            self::class,
            SignalSlotUtility::SIGNAL_QUEUEITEM_POSTPROCESS,
            [$queueId, &$field_array]
        );

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(QueueRepository::TABLE_NAME)
            ->update(
                QueueRepository::TABLE_NAME,
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
     * @return array|bool|mixed|string
     */
    public function readUrlFromArray($field_array)
    {
        // Set exec_time to lock record:
        $field_array['exec_time'] = $this->getCurrentTime();
        $connectionForCrawlerQueue = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(QueueRepository::TABLE_NAME);
        $connectionForCrawlerQueue->insert(
            QueueRepository::TABLE_NAME,
            $field_array
        );
        $queueId = $field_array['qid'] = $connectionForCrawlerQueue->lastInsertId(QueueRepository::TABLE_NAME, 'qid');
        $result = $this->queueExecutor->executeQueueItem($field_array, $this);

        // Set result in log which also denotes the end of the processing of this entry.
        $field_array = ['result_data' => json_encode($result)];

        SignalSlotUtility::emitSignal(
            self::class,
            SignalSlotUtility::SIGNAL_QUEUEITEM_POSTPROCESS,
            [$queueId, &$field_array]
        );

        $connectionForCrawlerQueue->update(
            QueueRepository::TABLE_NAME,
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
     * @return array
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

        $queueRows = [];

        // Traverse page tree:
        foreach ($tree->tree as $data) {
            $this->MP = false;

            // recognize mount points
            if ($data['row']['doktype'] === PageRepository::DOKTYPE_MOUNTPOINT) {
                $mountpage = $this->pageRepository->getPage($data['row']['uid']);

                // fetch mounted pages
                $this->MP = $mountpage[0]['mount_pid'] . '-' . $data['row']['uid'];

                $mountTree = GeneralUtility::makeInstance(PageTreeView::class);
                $mountTree->init('AND ' . $perms_clause);
                $mountTree->getTree($mountpage[0]['mount_pid'], $depth);

                foreach ($mountTree->tree as $mountData) {
                    $queueRows = array_merge($queueRows, $this->drawURLs_addRowsForPage(
                        $mountData['row'],
                        BackendUtility::getRecordTitle('pages', $mountData['row'], true),
                        (string) $data['HTML']
                    ));
                }

                // replace page when mount_pid_ol is enabled
                if ($mountpage[0]['mount_pid_ol']) {
                    $data['row']['uid'] = $mountpage[0]['mount_pid'];
                } else {
                    // if the mount_pid_ol is not set the MP must not be used for the mountpoint page
                    $this->MP = false;
                }
            }

            $queueRows = array_merge($queueRows, $this->drawURLs_addRowsForPage(
                $data['row'],
                BackendUtility::getRecordTitle('pages', $data['row'], true),
                (string) $data['HTML']
            ));
        }

        return $queueRows;
    }

    /**
     * Create the rows for display of the page tree
     * For each page a number of rows are shown displaying GET variable configuration
     */
    public function drawURLs_addRowsForPage(array $pageRow, string $pageTitle, string $pageTitleHTML = ''): array
    {
        $skipMessage = '';

        // Get list of configurations
        $configurations = $this->getUrlsForPageRow($pageRow, $skipMessage);
        $configurations = ConfigurationService::removeDisallowedConfigurations($this->incomingConfigurationSelection, $configurations);

        // Traverse parameter combinations:
        $c = 0;

        $queueRowCollection = [];

        if (! empty($configurations)) {
            foreach ($configurations as $confKey => $confArray) {

                // Title column:
                if (! $c) {
                    $queueRow = new QueueRow($pageTitle);
                    $queueRow->setPageTitleHTML($pageTitleHTML);
                } else {
                    $queueRow = new QueueRow();
                    $queueRow->setPageTitleHTML($pageTitleHTML);
                }

                if (! in_array($pageRow['uid'], $this->configurationService->expandExcludeString($confArray['subCfg']['exclude'] ?? ''), true)) {

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
                        // if empty the urls won't be filtered by processing instructions
                        $this->incomingProcInstructions
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
                    $queueRowOptionCollection = [];
                    if ($confArray['subCfg']['userGroups'] ?? false) {
                        $queueRowOptionCollection[] = 'User Groups: ' . $confArray['subCfg']['userGroups'];
                    }
                    if ($confArray['subCfg']['procInstrFilter'] ?? false) {
                        $queueRowOptionCollection[] = 'ProcInstr: ' . $confArray['subCfg']['procInstrFilter'];
                    }

                    // Remove empty array entries;
                    $queueRowOptionCollection = array_filter($queueRowOptionCollection);

                    $parameterConfig = nl2br(htmlspecialchars(rawurldecode(trim(str_replace('&', chr(10) . '&', GeneralUtility::implodeArrayForUrl('', $confArray['paramParsed'] ?? []))))));
                    $queueRow->setValuesExpanded($paramExpanded);
                    $queueRow->setConfigurationKey($confKey);
                    $queueRow->setUrls($urlList);
                    $queueRow->setOptions($queueRowOptionCollection);
                    $queueRow->setParameters(DebugUtility::viewArray($confArray['subCfg']['procInstrParams.'] ?? []));
                    $queueRow->setParameterConfig($parameterConfig);

                    $queueRowCollection[] = $queueRow;
                } else {
                    $queueRow->setConfigurationKey($confKey);
                    $queueRow->setMessage('(Page is excluded in this configuration)');
                    $queueRowCollection[] = $queueRow;
                }

                $c++;
            }
        } else {
            $message = ! empty($skipMessage) ? ' (' . $skipMessage . ')' : '';
            $queueRow = new QueueRow($pageTitle);
            $queueRow->setPageTitleHTML($pageTitleHTML);
            $queueRow->setMessage($message);
            $queueRowCollection[] = $queueRow;
        }

        return $queueRowCollection;
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

    protected function getPageService(): PageService
    {
        return new PageService();
    }

    private function getMaximumUrlsToCompile(): int
    {
        return $this->maximumUrlsToCompile;
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
     * @return QueryBuilder
     */
    private function getQueryBuilder(string $table)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }
}
