<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
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
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\EventDispatcher;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Utility\PhpBinaryUtility;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

/**
 * Function for Info module, containing three main actions:
 * - List of all queued items
 * - Log functionality
 * - Process overview
 */
class BackendModule
{
    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    /**
     * The current page ID
     * @var int
     */
    protected $id;

    // Internal, dynamic:
    protected $duplicateTrack = [];

    protected $submitCrawlUrls = false;

    protected $downloadCrawlUrls = false;

    protected $scheduledTime = 0;

    protected $reqMinute = 1000;

    /**
     * @var array holds the selection of configuration from the configuration selector box
     */
    protected $incomingConfigurationSelection = [];

    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    /**
     * @var array
     */
    protected $CSVaccu = [];

    /**
     * If true the user requested a CSV export of the queue
     *
     * @var boolean
     */
    protected $CSVExport = false;

    /**
     * @var array
     */
    protected $downloadUrls = [];

    /**
     * Holds the configuration from ext_conf_template loaded by getExtensionConfiguration()
     *
     * @var array
     */
    protected $extensionSettings = [];

    /**
     * Indicate that an flash message with an error is present.
     *
     * @var boolean
     */
    protected $isErrorDetected = false;

    /**
     * @var ProcessService
     */
    protected $processManager;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    public function __construct()
    {
        $objectManger = GeneralUtility::makeInstance(ObjectManager::class);
        $this->processManager = $objectManger->get(ProcessService::class);
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_crawler_queue');
        $this->queueRepository = $objectManger->get(QueueRepository::class);
        $this->initializeView();
        $this->extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Called by the InfoModuleController
     */
    public function init(InfoModuleController $pObj): void
    {
        $this->pObj = $pObj;
        $this->id = (int) GeneralUtility::_GP('id');
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
    }

    /**
     * Additions to the function menu array
     *
     * @return array Menu array
     */
    public function modMenu(): array
    {
        return [
            'depth' => [
                0 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                99 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
            'crawlaction' => [
                'start' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.start'),
                'log' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.log'),
                'multiprocess' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.multiprocess'),
            ],
            'log_resultLog' => '',
            'log_feVars' => '',
            'processListMode' => '',
            'log_display' => [
                'all' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.all'),
                'pending' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.pending'),
                'finished' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.finished'),
            ],
            'itemsPerPage' => [
                '5' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage.5'),
                '10' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage.10'),
                '50' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage.50'),
                '0' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage.0'),
            ],
        ];
    }

    public function main(): string
    {
        if (empty($this->pObj->MOD_SETTINGS['processListMode'])) {
            $this->pObj->MOD_SETTINGS['processListMode'] = 'simple';
        }
        $this->view->assign('currentPageId', $this->id);

        $selectedAction = (string) $this->pObj->MOD_SETTINGS['crawlaction'] ?? 'start';

        // Type function menu:
        $actionDropdown = BackendUtility::getFuncMenu(
            $this->id,
            'SET[crawlaction]',
            $selectedAction,
            $this->pObj->MOD_MENU['crawlaction']
        );

        $theOutput = '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('title')) . '</h2>' . $actionDropdown;

        // Branch based on type:
        switch ($selectedAction) {
            case 'start':
                $theOutput .= $this->showCrawlerInformationAction();
                break;
            case 'log':

                // Additional menus for the log type:
                $theOutput .= BackendUtility::getFuncMenu(
                    $this->id,
                    'SET[depth]',
                    $this->pObj->MOD_SETTINGS['depth'],
                    $this->pObj->MOD_MENU['depth']
                );

                $quiPart = GeneralUtility::_GP('qid_details') ? '&qid_details=' . (int) GeneralUtility::_GP('qid_details') : '';
                $setId = (int) GeneralUtility::_GP('setID');

                $theOutput .= '<br><br>' .
                    $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.display') . ': ' . BackendUtility::getFuncMenu(
                        $this->id,
                        'SET[log_display]',
                        $this->pObj->MOD_SETTINGS['log_display'],
                        $this->pObj->MOD_MENU['log_display'],
                        'index.php',
                        '&setID=' . $setId
                    ) . ' ' .
                    $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.showresultlog') . ': ' . BackendUtility::getFuncCheck(
                        $this->id,
                        'SET[log_resultLog]',
                        $this->pObj->MOD_SETTINGS['log_resultLog'],
                        'index.php',
                        '&setID=' . $setId . $quiPart
                    ) . ' ' .
                    $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.showfevars') . ': ' . BackendUtility::getFuncCheck(
                        $this->id,
                        'SET[log_feVars]',
                        $this->pObj->MOD_SETTINGS['log_feVars'],
                        'index.php',
                        '&setID=' . $setId . $quiPart
                    ) . ' ' .
                    $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage') . ': ' .
                    BackendUtility::getFuncMenu(
                        $this->id,
                        'SET[itemsPerPage]',
                        $this->pObj->MOD_SETTINGS['itemsPerPage'],
                        $this->pObj->MOD_MENU['itemsPerPage']
                    );
                $theOutput .= $this->showLogAction();
                break;
            case 'multiprocess':
                $theOutput .= $this->processOverviewAction();
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
     * Show a list of URLs to be crawled for each page
     */
    protected function showCrawlerInformationAction(): string
    {
        $this->view->setTemplate('ShowCrawlerInformation');
        if (empty($this->id)) {
            $this->addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noPageSelected'));
        } else {
            $crawlerParameter = GeneralUtility::_GP('_crawl');
            $downloadParameter = GeneralUtility::_GP('_download');

            $this->duplicateTrack = [];
            $this->submitCrawlUrls = isset($crawlerParameter);
            $this->downloadCrawlUrls = isset($downloadParameter);
            $this->makeCrawlerProcessableChecks();

            switch ((string) GeneralUtility::_GP('tstamp')) {
                case 'midnight':
                    $this->scheduledTime = mktime(0, 0, 0);
                    break;
                case '04:00':
                    $this->scheduledTime = mktime(0, 0, 0) + 4 * 3600;
                    break;
                case 'now':
                default:
                    $this->scheduledTime = time();
                    break;
            }

            $this->incomingConfigurationSelection = GeneralUtility::_GP('configurationSelection');
            $this->incomingConfigurationSelection = is_array($this->incomingConfigurationSelection) ? $this->incomingConfigurationSelection : [];

            $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
            $this->crawlerController->setAccessMode('gui');
            $this->crawlerController->setID = GeneralUtility::md5int(microtime());

            $code = '';
            $noConfigurationSelected = empty($this->incomingConfigurationSelection)
                || (count($this->incomingConfigurationSelection) === 1 && empty($this->incomingConfigurationSelection[0]));
            if ($noConfigurationSelected) {
                $this->addWarningMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noConfigSelected'));
            } else {
                if ($this->submitCrawlUrls) {
                    $reason = new Reason();
                    $reason->setReason(Reason::REASON_GUI_SUBMIT);
                    $reason->setDetailText('The user ' . $GLOBALS['BE_USER']->user['username'] . ' added pages to the crawler queue manually');

                    EventDispatcher::getInstance()->post(
                        'invokeQueueChange',
                        $this->findCrawler()->setID,
                        ['reason' => $reason]
                    );
                }

                $code = $this->crawlerController->getPageTreeAndUrls(
                    $this->id,
                    $this->pObj->MOD_SETTINGS['depth'],
                    $this->scheduledTime,
                    $this->reqMinute,
                    $this->submitCrawlUrls,
                    $this->downloadCrawlUrls,
                    [], // Do not filter any processing instructions
                    $this->incomingConfigurationSelection
                );
            }

            $this->downloadUrls = $this->crawlerController->downloadUrls;
            $this->duplicateTrack = $this->crawlerController->duplicateTrack;

            $this->view->assign('noConfigurationSelected', $noConfigurationSelected);
            $this->view->assign('submitCrawlUrls', $this->submitCrawlUrls);
            $this->view->assign('amountOfUrls', count(array_keys($this->duplicateTrack)));
            $this->view->assign('selectors', $this->generateConfigurationSelectors());
            $this->view->assign('code', $code);

            // Download Urls to crawl:
            if ($this->downloadCrawlUrls) {
                // Creating output header:
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=CrawlerUrls.txt');

                // Printing the content of the CSV lines:
                echo implode(chr(13) . chr(10), $this->downloadUrls);
                exit;
            }
        }
        return $this->view->render();
    }

    /**
     * Generates the configuration selectors for compiling URLs:
     */
    protected function generateConfigurationSelectors(): array
    {
        $selectors = [];
        $selectors['depth'] = $this->selectorBox(
            [
                0 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                99 => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
            'SET[depth]',
            $this->pObj->MOD_SETTINGS['depth'],
            false
        );

        // Configurations
        $availableConfigurations = $this->crawlerController->getConfigurationsForBranch((int) $this->id, $this->pObj->MOD_SETTINGS['depth'] ?: 0);
        $selectors['configurations'] = $this->selectorBox(
            empty($availableConfigurations) ? [] : array_combine($availableConfigurations, $availableConfigurations),
            'configurationSelection',
            $this->incomingConfigurationSelection,
            true
        );

        // Scheduled time:
        $selectors['scheduled'] = $this->selectorBox(
            [
                'now' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.time.now'),
                'midnight' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.time.midnight'),
                '04:00' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.time.4am'),
            ],
            'tstamp',
            GeneralUtility::_POST('tstamp'),
            false
        );

        return $selectors;
    }

    /*******************************
     *
     * Shows log of indexed URLs
     *
     ******************************/

    protected function showLogAction(): string
    {
        $this->view->setTemplate('ShowLog');
        if (empty($this->id)) {
            $this->addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noPageSelected'));
        } else {
            $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
            $this->crawlerController->setAccessMode('gui');
            $this->crawlerController->setID = GeneralUtility::md5int(microtime());

            $csvExport = GeneralUtility::_POST('_csv');
            $this->CSVExport = isset($csvExport);

            // Read URL:
            if (GeneralUtility::_GP('qid_read')) {
                $this->crawlerController->readUrl((int) GeneralUtility::_GP('qid_read'), true);
            }

            // Look for set ID sent - if it is, we will display contents of that set:
            $showSetId = (int) GeneralUtility::_GP('setID');

            $queueId = GeneralUtility::_GP('qid_details');
            $this->view->assign('queueId', $queueId);
            $this->view->assign('setId', $showSetId);
            // Show details:
            if ($queueId) {
                // Get entry record:
                $q_entry = $this->queryBuilder
                    ->from('tx_crawler_queue')
                    ->select('*')
                    ->where(
                        $this->queryBuilder->expr()->eq('qid', $this->queryBuilder->createNamedParameter($queueId))
                    )
                    ->execute()
                    ->fetch();

                // Explode values
                $q_entry['parameters'] = unserialize($q_entry['parameters']);
                $q_entry['result_data'] = unserialize($q_entry['result_data']);
                $resStatus = $this->getResStatus($q_entry['result_data']);
                if (is_array($q_entry['result_data'])) {
                    $q_entry['result_data']['content'] = unserialize($q_entry['result_data']['content']);
                    if (! $this->pObj->MOD_SETTINGS['log_resultLog']) {
                        unset($q_entry['result_data']['content']['log']);
                    }
                }

                $this->view->assign('queueStatus', $resStatus);
                $this->view->assign('queueDetails', DebugUtility::viewArray($q_entry));
            } else {
                // Show list
                // Drawing tree:
                $tree = GeneralUtility::makeInstance(PageTreeView::class);
                $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
                $tree->init('AND ' . $perms_clause);

                // Set root row:
                $pageinfo = BackendUtility::readPageAccess(
                    $this->id,
                    $perms_clause
                );
                $HTML = $this->iconFactory->getIconForRecord('pages', $pageinfo, Icon::SIZE_SMALL)->render();
                $tree->tree[] = [
                    'row' => $pageinfo,
                    'HTML' => $HTML,
                ];

                // Get branch beneath:
                if ($this->pObj->MOD_SETTINGS['depth']) {
                    $tree->getTree($this->id, $this->pObj->MOD_SETTINGS['depth']);
                }

                // If Flush button is pressed, flush tables instead of selecting entries:
                if (GeneralUtility::_POST('_flush')) {
                    $doFlush = true;
                    $doFullFlush = false;
                } elseif (GeneralUtility::_POST('_flush_all')) {
                    $doFlush = true;
                    $doFullFlush = true;
                } else {
                    $doFlush = false;
                    $doFullFlush = false;
                }
                $itemsPerPage = (int) $this->pObj->MOD_SETTINGS['itemsPerPage'];
                // Traverse page tree:
                $code = '';
                $count = 0;
                foreach ($tree->tree as $data) {
                    // Get result:
                    $logEntriesOfPage = $this->crawlerController->getLogEntriesForPageId(
                        (int) $data['row']['uid'],
                        $this->pObj->MOD_SETTINGS['log_display'],
                        $doFlush,
                        $doFullFlush,
                        $itemsPerPage
                    );

                    $code .= $this->drawLog_addRows(
                        $logEntriesOfPage,
                        $data['HTML'] . BackendUtility::getRecordTitle('pages', $data['row'], true)
                    );
                    if (++$count === 1000) {
                        break;
                    }
                }
                $this->view->assign('code', $code);
            }

            if ($this->CSVExport) {
                $this->outputCsvFile();
            }
        }
        $this->view->assign('showResultLog', (bool) $this->pObj->MOD_SETTINGS['log_resultLog']);
        $this->view->assign('showFeVars', (bool) $this->pObj->MOD_SETTINGS['log_feVars']);
        return $this->view->render();
    }

    /**
     * Outputs the CSV file and sets the correct headers
     */
    protected function outputCsvFile(): void
    {
        if (! count($this->CSVaccu)) {
            $this->addWarningMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:message.canNotExportEmptyQueueToCsvText'));
            return;
        }
        $csvLines = [];

        // Field names:
        reset($this->CSVaccu);
        $fieldNames = array_keys(current($this->CSVaccu));
        $csvLines[] = CsvUtility::csvValues($fieldNames);

        // Data:
        foreach ($this->CSVaccu as $row) {
            $csvLines[] = CsvUtility::csvValues($row);
        }

        // Creating output header:
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=CrawlerLog.csv');

        // Printing the content of the CSV lines:
        echo implode(chr(13) . chr(10), $csvLines);
        exit;
    }

    /**
     * Create the rows for display of the page tree
     * For each page a number of rows are shown displaying GET variable configuration
     *
     * @param array $logEntriesOfPage Log items of one page
     * @param string $titleString Title string
     * @return string HTML <tr> content (one or more)
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function drawLog_addRows(array $logEntriesOfPage, string $titleString): string
    {
        $colSpan = 9
                + ($this->pObj->MOD_SETTINGS['log_resultLog'] ? -1 : 0)
                + ($this->pObj->MOD_SETTINGS['log_feVars'] ? 3 : 0);

        if (! empty($logEntriesOfPage)) {
            $setId = (int) GeneralUtility::_GP('setID');
            $refreshIcon = $this->iconFactory->getIcon('actions-system-refresh', Icon::SIZE_SMALL);
            // Traverse parameter combinations:
            $c = 0;
            $content = '';
            foreach ($logEntriesOfPage as $vv) {
                // Title column:
                if (! $c) {
                    $titleClm = '<td rowspan="' . count($logEntriesOfPage) . '">' . $titleString . '</td>';
                } else {
                    $titleClm = '';
                }

                // Result:
                $resLog = $this->getResultLog($vv);

                $resultData = $vv['result_data'] ? unserialize($vv['result_data']) : [];
                $resStatus = $this->getResStatus($resultData);

                // Compile row:
                $parameters = unserialize($vv['parameters']);

                // Put data into array:
                $rowData = [];
                if ($this->pObj->MOD_SETTINGS['log_resultLog']) {
                    $rowData['result_log'] = $resLog;
                } else {
                    $rowData['scheduled'] = ($vv['scheduled'] > 0) ? BackendUtility::datetime($vv['scheduled']) : ' ' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.immediate');
                    $rowData['exec_time'] = $vv['exec_time'] ? BackendUtility::datetime($vv['exec_time']) : '-';
                }
                $rowData['result_status'] = GeneralUtility::fixed_lgd_cs($resStatus, 50);
                $rowData['url'] = '<a href="' . htmlspecialchars($parameters['url']) . '" target="_newWIndow">' . htmlspecialchars($parameters['url']) . '</a>';
                $rowData['feUserGroupList'] = $parameters['feUserGroupList'];
                $rowData['procInstructions'] = is_array($parameters['procInstructions']) ? implode('; ', $parameters['procInstructions']) : '';
                $rowData['set_id'] = $vv['set_id'];

                if ($this->pObj->MOD_SETTINGS['log_feVars']) {
                    $resFeVars = $this->getResFeVars($resultData ?: []);
                    $rowData['tsfe_id'] = $resFeVars['id'];
                    $rowData['tsfe_gr_list'] = $resFeVars['gr_list'];
                    $rowData['tsfe_no_cache'] = $resFeVars['no_cache'];
                }

                // Put rows together:
                $content .= '
					<tr>
						' . $titleClm . '
						<td><a href="' . $this->getInfoModuleUrl(['qid_details' => $vv['qid'], 'setID' => $setId]) . '">' . htmlspecialchars($vv['qid']) . '</a></td>
						<td><a href="' . $this->getInfoModuleUrl(['qid_read' => $vv['qid'], 'setID' => $setId]) . '">' . $refreshIcon . '</a></td>';
                foreach ($rowData as $fKey => $value) {
                    if ($fKey === 'url') {
                        $content .= '<td>' . $value . '</td>';
                    } else {
                        $content .= '<td>' . nl2br(htmlspecialchars($value)) . '</td>';
                    }
                }
                $content .= '</tr>';
                $c++;

                if ($this->CSVExport) {
                    // Only for CSV (adding qid and scheduled/exec_time if needed):
                    $rowData['result_log'] = implode('// ', explode(chr(10), $resLog));
                    $rowData['qid'] = $vv['qid'];
                    $rowData['scheduled'] = BackendUtility::datetime($vv['scheduled']);
                    $rowData['exec_time'] = $vv['exec_time'] ? BackendUtility::datetime($vv['exec_time']) : '-';
                    $this->CSVaccu[] = $rowData;
                }
            }
        } else {
            // Compile row:
            $content = '
				<tr>
					<td>' . $titleString . '</td>
					<td colspan="' . $colSpan . '"><em>' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noentries') . '</em></td>
				</tr>';
        }

        return $content;
    }

    /**
     * Find Fe vars
     */
    protected function getResFeVars(array $resultData): array
    {
        if (empty($resultData)) {
            return [];
        }
        $requestResult = unserialize($resultData['content']);
        return $requestResult['vars'];
    }

    /**
     * Extract the log information from the current row and retrive it as formatted string.
     *
     * @param array $resultRow
     * @return string
     */
    protected function getResultLog($resultRow)
    {
        $content = '';
        if (is_array($resultRow) && array_key_exists('result_data', $resultRow)) {
            $requestContent = unserialize($resultRow['result_data']);
            $requestResult = unserialize($requestContent['content']);

            if (is_array($requestResult) && array_key_exists('log', $requestResult)) {
                $content = implode(chr(10), $requestResult['log']);
            }
        }
        return $content;
    }

    protected function getResStatus($requestContent): string
    {
        if (empty($requestContent)) {
            return '-';
        }
        $requestResult = unserialize($requestContent['content']);
        if (is_array($requestResult)) {
            if (empty($requestResult['errorlog'])) {
                return 'OK';
            }
            return implode("\n", $requestResult['errorlog']);
        }
        return 'Error: ' . substr(preg_replace('/\s+/', ' ', strip_tags($requestResult)), 0, 10000) . '...';
    }

    /**
     * This method is used to show an overview about the active an the finished crawling processes
     *
     * @return string
     */
    protected function processOverviewAction()
    {
        $this->view->setTemplate('ProcessOverview');
        $this->runRefreshHooks();
        $this->makeCrawlerProcessableChecks();

        try {
            $this->handleProcessOverviewActions();
        } catch (\Throwable $e) {
            $this->addErrorMessage($e->getMessage());
        }

        $processRepository = new ProcessRepository();
        $queueRepository = new QueueRepository();

        $mode = $this->pObj->MOD_SETTINGS['processListMode'];
        if ($mode === 'simple') {
            $allProcesses = $processRepository->findAllActive();
        } else {
            $allProcesses = $processRepository->findAll();
        }
        $isCrawlerEnabled = ! $this->findCrawler()->getDisabled() && ! $this->isErrorDetected;
        $currentActiveProcesses = $processRepository->countActive();
        $maxActiveProcesses = MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1);
        $this->view->assignMultiple([
            'pageId' => (int) $this->id,
            'refreshLink' => $this->getRefreshLink(),
            'addLink' => $this->getAddLink($currentActiveProcesses, $maxActiveProcesses, $isCrawlerEnabled),
            'modeLink' => $this->getModeLink($mode),
            'enableDisableToggle' => $this->getEnableDisableLink($isCrawlerEnabled),
            'processCollection' => $allProcesses,
            'cliPath' => $this->processManager->getCrawlerCliPath(),
            'isCrawlerEnabled' => $isCrawlerEnabled,
            'totalUnprocessedItemCount' => $queueRepository->countAllPendingItems(),
            'assignedUnprocessedItemCount' => $queueRepository->countAllAssignedPendingItems(),
            'activeProcessCount' => $currentActiveProcesses,
            'maxActiveProcessCount' => $maxActiveProcesses,
            'mode' => $mode,
        ]);

        return $this->view->render();
    }

    /**
     * Returns a tag for the refresh icon
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getRefreshLink(): string
    {
        return $this->getLinkButton(
            'actions-refresh',
            $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.refresh'),
            $this->getInfoModuleUrl(['SET[\'crawleraction\']' => 'crawleraction', 'id' => $this->id])
        );
    }

    /**
     * Returns a link for the panel to enable or disable the crawler
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getEnableDisableLink(bool $isCrawlerEnabled): string
    {
        if ($isCrawlerEnabled) {
            // TODO: Icon Should be bigger + Perhaps better icon
            return $this->getLinkButton(
                'tx-crawler-stop',
                $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.disablecrawling'),
                $this->getInfoModuleUrl(['action' => 'stopCrawling'])
            );
        }
        // TODO: Icon Should be bigger
        return $this->getLinkButton(
                'tx-crawler-start',
                $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.enablecrawling'),
                $this->getInfoModuleUrl(['action' => 'resumeCrawling'])
            );
    }

    /**
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getModeLink(string $mode): string
    {
        if ($mode === 'detail') {
            return $this->getLinkButton(
                'actions-document-view',
                $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.show.running'),
                $this->getInfoModuleUrl(['SET[\'processListMode\']' => 'simple'])
            );
        } elseif ($mode === 'simple') {
            return $this->getLinkButton(
                'actions-document-view',
                $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.show.all'),
                $this->getInfoModuleUrl(['SET[\'processListMode\']' => 'detail'])
            );
        }
        return '';
    }

    /**
     * @param $currentActiveProcesses
     * @param $maxActiveProcesses
     * @param $isCrawlerEnabled
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getAddLink($currentActiveProcesses, $maxActiveProcesses, $isCrawlerEnabled): string
    {
        if (! $isCrawlerEnabled) {
            return '';
        }
        if (MathUtility::convertToPositiveInteger($currentActiveProcesses) >= MathUtility::convertToPositiveInteger($maxActiveProcesses)) {
            return '';
        }
        return $this->getLinkButton(
            'actions-add',
            $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.process.add'),
            $this->getInfoModuleUrl(['action' => 'addProcess'])
        );
    }

    /**
     * Verify that the crawler is executable.
     */
    protected function makeCrawlerProcessableChecks(): void
    {
        if (! $this->isPhpForkAvailable()) {
            $this->addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:message.noPhpForkAvailable'));
        }

        $exitCode = 0;
        $out = [];
        CommandUtility::exec(
            PhpBinaryUtility::getPhpBinary() . ' -v',
            $out,
            $exitCode
        );
        if ($exitCode > 0) {
            $this->addErrorMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:message.phpBinaryNotFound'), htmlspecialchars($this->extensionSettings['phpPath'])));
        }
    }

    /**
     * Indicate that the required PHP method "popen" is
     * available in the system.
     */
    protected function isPhpForkAvailable(): bool
    {
        return function_exists('popen');
    }

    /**
     * Method to handle incomming actions of the process overview
     *
     * @throws \Exception
     */
    protected function handleProcessOverviewActions(): void
    {
        $crawler = $this->findCrawler();

        switch (GeneralUtility::_GP('action')) {
            case 'stopCrawling':
                //set the cli status to disable (all processes will be terminated)
                $crawler->setDisabled(true);
                break;
            case 'resumeCrawling':
                //set the cli status to end (all processes will be terminated)
                $crawler->setDisabled(false);
                break;
            case 'addProcess':
                if ($this->processManager->startProcess() === false) {
                    throw new \Exception($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.newprocesserror'));
                }
                $this->addNoticeMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.newprocess'));
                break;
        }
    }

    /**
     * Returns the singleton instance of the crawler.
     */
    protected function findCrawler(): CrawlerController
    {
        if (! $this->crawlerController instanceof CrawlerController) {
            $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
        }
        return $this->crawlerController;
    }

    /*****************************
     *
     * General Helper Functions
     *
     *****************************/

    /**
     * This method is used to add a message to the internal queue
     *
     * @param  string  the message itself
     * @param  integer message level (-1 = success (default), 0 = info, 1 = notice, 2 = warning, 3 = error)
     */
    protected function addMessage($message, $severity = FlashMessage::OK): void
    {
        $message = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            '',
            $severity
        );

        // TODO:
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageService->getMessageQueueByIdentifier()->addMessage($message);
    }

    /**
     * Add notice message to the user interface.
     *
     * @param string The message
     */
    protected function addNoticeMessage(string $message): void
    {
        $this->addMessage($message, FlashMessage::NOTICE);
    }

    /**
     * Add error message to the user interface.
     *
     * @param string The message
     */
    protected function addErrorMessage(string $message): void
    {
        $this->isErrorDetected = true;
        $this->addMessage($message, FlashMessage::ERROR);
    }

    /**
     * Add error message to the user interface.
     *
     * @param string The message
     */
    protected function addWarningMessage(string $message): void
    {
        $this->addMessage($message, FlashMessage::WARNING);
    }

    /**
     * Create selector box
     *
     * @param	array		$optArray Options key(value) => label pairs
     * @param	string		$name Selector box name
     * @param	string|array		$value Selector box value (array for multiple...)
     * @param	boolean		$multiple If set, will draw multiple box.
     *
     * @return	string		HTML select element
     */
    protected function selectorBox($optArray, $name, $value, bool $multiple): string
    {
        $options = [];
        foreach ($optArray as $key => $val) {
            $selected = (! $multiple && ! strcmp($value, $key)) || ($multiple && in_array($key, (array) $value, true));
            $options[] = '
				<option value="' . htmlspecialchars($key) . '"' . ($selected ? ' selected="selected"' : '') . '>' . htmlspecialchars($val) . '</option>';
        }

        return '<select class="form-control" name="' . htmlspecialchars($name . ($multiple ? '[]' : '')) . '"' . ($multiple ? ' multiple' : '') . '>' . implode('', $options) . '</select>';
    }

    /**
     * Activate hooks
     */
    protected function runRefreshHooks(): void
    {
        $crawlerLib = GeneralUtility::makeInstance(CrawlerController::class);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['refresh_hooks'] ?? [] as $objRef) {
            $hookObj = GeneralUtility::makeInstance($objRef);
            if (is_object($hookObj)) {
                $hookObj->crawler_init($crawlerLib);
            }
        }
    }

    protected function initializeView(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:crawler/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:crawler/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:crawler/Resources/Private/Templates/Backend']);
        $view->getRequest()->setControllerExtensionName('Crawler');
        $this->view = $view;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getLinkButton(string $iconIdentifier, string $title, UriInterface $href): string
    {
        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        return (string) $buttonBar->makeLinkButton()
            ->setHref((string) $href)
            ->setIcon($this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL))
            ->setTitle($title)
            ->setShowLabelText(true);
    }

    /**
     * Returns the URL to the current module, including $_GET['id'].
     *
     * @param array $uriParameters optional parameters to add to the URL
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getInfoModuleUrl(array $uriParameters = []): Uri
    {
        if (GeneralUtility::_GP('id')) {
            $uriParameters = array_merge($uriParameters, [
                'id' => GeneralUtility::_GP('id'),
            ]);
        }
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return $uriBuilder->buildUriFromRoute('web_info', $uriParameters);
    }
}
