<?php
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

use AOE\Crawler\Backend\View\PaginationView;
use AOE\Crawler\Backend\View\ProcessListView;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Event\EventDispatcher;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Utility\IconUtility;
use AOE\Crawler\Utility\SignalSlotUtility;
use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class BackendModule
 *
 * @package AOE\Crawler\Backend
 */
class BackendModule extends AbstractFunctionModule
{
    // Internal, dynamic:
    public $duplicateTrack = [];
    public $submitCrawlUrls = false;
    public $downloadCrawlUrls = false;

    public $scheduledTime = 0;
    public $reqMinute = 0;

    /**
     * @var array holds the selection of configuration from the configuration selector box
     */
    public $incomingConfigurationSelection = [];

    /**
     * @var CrawlerController
     */
    public $crawlerController;

    public $CSVaccu = [];

    /**
     * If true the user requested a CSV export of the queue
     *
     * @var boolean
     */
    public $CSVExport = false;

    public $downloadUrls = [];

    /**
     * Holds the configuration from ext_conf_template loaded by loadExtensionSettings()
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
     * the constructor
     */
    public function __construct()
    {
        $this->processManager = new ProcessService();
    }

    /**
     * Additions to the function menu array
     *
     * @return array Menu array
     */
    public function modMenu()
    {
        global $LANG;

        return [
            'depth' => [
                0 => $LANG->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $LANG->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $LANG->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $LANG->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $LANG->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                99 => $LANG->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
            'crawlaction' => [
                'start' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.start'),
                'log' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.log'),
                'multiprocess' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.multiprocess')
            ],
            'log_resultLog' => '',
            'log_feVars' => '',
            'processListMode' => '',
            'log_display' => [
                'all' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.all'),
                'pending' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.pending'),
                'finished' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.finished')
            ],
            'itemsPerPage' => [
                '5' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage.5'),
                '10' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage.10'),
                '50' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage.50'),
                '0' => $LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage.0')
            ]
        ];
    }

    /**
     * Load extension settings
     *
     * @return void
     */
    protected function loadExtensionSettings()
    {
        $this->extensionSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['crawler']);
    }

    /**
     * Main function
     *
     * @return	string		HTML output
     */
    public function main()
    {
        global $LANG, $BACK_PATH;

        $this->incLocalLang();

        $this->loadExtensionSettings();
        if (empty($this->pObj->MOD_SETTINGS['processListMode'])) {
            $this->pObj->MOD_SETTINGS['processListMode'] = 'simple';
        }

        // Set CSS styles specific for this document:
        $this->pObj->content = str_replace('/*###POSTCSSMARKER###*/', '
			TABLE.c-list TR TD { white-space: nowrap; vertical-align: top; }
		', $this->pObj->content);

        $this->pObj->content .= '<style type="text/css"><!--
			table.url-table,
			table.param-expanded,
			table.crawlerlog {
				border-bottom: 1px solid grey;
				border-spacing: 0;
				border-collapse: collapse;
			}
			table.crawlerlog td,
			table.url-table td {
				border: 1px solid lightgrey;
				border-bottom: 1px solid grey;
				 white-space: nowrap; vertical-align: top;
			}
		--></style>
		<link rel="stylesheet" type="text/css" href="' . $BACK_PATH . '../typo3conf/ext/crawler/template/res.css" />
		';

        // Type function menu:
        $h_func = BackendUtility::getFuncMenu(
            $this->pObj->id,
            'SET[crawlaction]',
            $this->pObj->MOD_SETTINGS['crawlaction'],
            $this->pObj->MOD_MENU['crawlaction'],
            'index.php'
        );

        // Additional menus for the log type:
        if ($this->pObj->MOD_SETTINGS['crawlaction'] === 'log') {
            $h_func .= BackendUtility::getFuncMenu(
                $this->pObj->id,
                'SET[depth]',
                $this->pObj->MOD_SETTINGS['depth'],
                $this->pObj->MOD_MENU['depth'],
                'index.php'
            );

            $quiPart = GeneralUtility::_GP('qid_details') ? '&qid_details=' . intval(GeneralUtility::_GP('qid_details')) : '';

            $setId = intval(GeneralUtility::_GP('setID'));

            $h_func .= '<hr/>' .
                    $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.display') . ': ' . BackendUtility::getFuncMenu($this->pObj->id, 'SET[log_display]', $this->pObj->MOD_SETTINGS['log_display'], $this->pObj->MOD_MENU['log_display'], 'index.php', '&setID=' . $setId) . ' - ' .
                    $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.showresultlog') . ': ' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[log_resultLog]', $this->pObj->MOD_SETTINGS['log_resultLog'], 'index.php', '&setID=' . $setId . $quiPart) . ' - ' .
                    $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.showfevars') . ': ' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[log_feVars]', $this->pObj->MOD_SETTINGS['log_feVars'], 'index.php', '&setID=' . $setId . $quiPart) . ' - ' .
                    $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.itemsPerPage') . ': ' .
                    BackendUtility::getFuncMenu(
                        $this->pObj->id,
                        'SET[itemsPerPage]',
                        $this->pObj->MOD_SETTINGS['itemsPerPage'],
                        $this->pObj->MOD_MENU['itemsPerPage'],
                        'index.php'
                    );
        }

        $theOutput = $this->pObj->doc->section($LANG->getLL('title'), $h_func, false, true);

        // Branch based on type:
        switch ((string)$this->pObj->MOD_SETTINGS['crawlaction']) {
            case 'start':
                if (empty($this->pObj->id)) {
                    $this->addErrorMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noPageSelected'));
                } else {
                    $theOutput .= $this->pObj->doc->section('', $this->drawURLs(), false, true);
                }
                break;
            case 'log':
                if (empty($this->pObj->id)) {
                    $this->addErrorMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noPageSelected'));
                } else {
                    $theOutput .= $this->pObj->doc->section('', $this->drawLog(), false, true);
                }
                break;
            case 'cli':
                // TODO: drawCLIstatus is not defined, why did we refactor it away?
                $theOutput .= $this->pObj->doc->section('', $this->drawCLIstatus(), false, true);
                break;
            case 'multiprocess':
                $theOutput .= $this->pObj->doc->section('', $this->drawProcessOverviewAction(), false, true);
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
    public function drawURLs()
    {
        global $BACK_PATH, $BE_USER;

        $crawlerParameter = GeneralUtility::_GP('_crawl');
        $downloadParameter = GeneralUtility::_GP('_download');

        // Init:
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
        // $this->reqMinute = \TYPO3\CMS\Core\Utility\GeneralUtility::intInRange(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('perminute'),1,10000);
        // TODO: check relevance
        $this->reqMinute = 1000;

        $this->incomingConfigurationSelection = GeneralUtility::_GP('configurationSelection');
        $this->incomingConfigurationSelection = is_array($this->incomingConfigurationSelection) ? $this->incomingConfigurationSelection : [];

        $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
        $this->crawlerController->setAccessMode('gui');
        $this->crawlerController->setID = GeneralUtility::md5int(microtime());

        if (empty($this->incomingConfigurationSelection)
            || (count($this->incomingConfigurationSelection) == 1 && empty($this->incomingConfigurationSelection[0]))
            ) {
            $this->addWarningMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noConfigSelected'));
            $code = '
			<tr>
				<td colspan="7"><b>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noConfigSelected') . '</b></td>
			</tr>';
        } else {
            if ($this->submitCrawlUrls) {
                $reason = new Reason();
                $reason->setReason(Reason::REASON_GUI_SUBMIT);

                if ($BE_USER instanceof BackendUserAuthentication) {
                    $username = $BE_USER->user['username'];
                    $reason->setDetailText('The user ' . $username . ' added pages to the crawler queue manually ');
                }

                // The event dispatcher is deprecated since crawler v6.3.0, will be removed in crawler v7.0.0.
                // Please use the Signal instead.
                EventDispatcher::getInstance()->post(
                    'invokeQueueChange',
                    $this->findCrawler()->setID,
                    ['reason' => $reason]
                );

                SignalSlotUtility::emitSignal(
                    __CLASS__,
                    SignalSlotUtility::SIGNAL_INVOKE_QUEUE_CHANGE,
                    ['reason' => $reason]
                );
            }

            $code = $this->crawlerController->getPageTreeAndUrls(
                $this->pObj->id,
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

        $output = '';
        if ($code) {
            $output .= '<h3>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.configuration') . ':</h3>';
            $output .= '<input type="hidden" name="id" value="' . intval($this->pObj->id) . '" />';

            if (!$this->submitCrawlUrls) {
                $output .= $this->drawURLs_cfgSelectors() . '<br />';
                $output .= '<input type="submit" name="_update" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.triggerUpdate') . '" /> ';
                $output .= '<input type="submit" name="_crawl" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.triggerCrawl') . '" /> ';
                $output .= '<input type="submit" name="_download" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.triggerDownload') . '" /><br /><br />';
                $output .= $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.count') . ': ' . count(array_keys($this->duplicateTrack)) . '<br />';
                $output .= $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.curtime') . ': ' . date('H:i:s', time()) . '<br />';
                $output .= '<br />
					<table class="lrPadding c-list url-table">' .
                        $this->drawURLs_printTableHeader() .
                        $code .
                    '</table>';
            } else {
                $output .= count(array_keys($this->duplicateTrack)) . ' ' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.submitted') . '. <br /><br />';
                $output .= '<input type="submit" name="_" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.continue') . '" />';
                $output .= '<input type="submit" onclick="this.form.elements[\'SET[crawlaction]\'].value=\'log\';" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.continueinlog') . '" />';
            }
        }

        // Download Urls to crawl:
        if ($this->downloadCrawlUrls) {

                // Creating output header:
            $mimeType = 'application/octet-stream';
            Header('Content-Type: ' . $mimeType);
            Header('Content-Disposition: attachment; filename=CrawlerUrls.txt');

            // Printing the content of the CSV lines:
            echo implode(chr(13) . chr(10), $this->downloadUrls);

            exit;
        }

        return 	$output;
    }

    /**
     * Draws the configuration selectors for compiling URLs:
     *
     * @return	string		HTML table
     */
    public function drawURLs_cfgSelectors()
    {
        $cell = [];

        // depth
        $cell[] = $this->selectorBox(
            [
                0 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                99 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
            'SET[depth]',
            $this->pObj->MOD_SETTINGS['depth'],
            false
        );
        $availableConfigurations = $this->crawlerController->getConfigurationsForBranch($this->pObj->id, $this->pObj->MOD_SETTINGS['depth'] ? $this->pObj->MOD_SETTINGS['depth'] : 0);

        // Configurations
        $cell[] = $this->selectorBox(
            empty($availableConfigurations) ? [] : array_combine($availableConfigurations, $availableConfigurations),
            'configurationSelection',
            $this->incomingConfigurationSelection,
            true
        );

        // Scheduled time:
        $cell[] = $this->selectorBox(
            [
                'now' => $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.time.now'),
                'midnight' => $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.time.midnight'),
                '04:00' => $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.time.4am'),
            ],
            'tstamp',
            GeneralUtility::_POST('tstamp'),
            false
        );

        $output = '
			<table class="lrPadding c-list">
				<tr class="bgColor5 tableheader">
					<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.depth') . ':</td>
					<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.configurations') . ':</td>
					<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.scheduled') . ':</td>
				</tr>
				<tr class="bgColor4">
					<td valign="top">' . implode('</td>
					<td valign="top">', $cell) . '</td>
				</tr>
			</table>';

        return $output;
    }

    /**
     * Create Table header row for URL display
     *
     * @return	string		Table header
     */
    public function drawURLs_printTableHeader()
    {
        $content = '
			<tr class="bgColor5 tableheader">
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.pagetitle') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.key') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.parametercfg') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.values') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.urls') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.options') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.parameters') . ':</td>
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
    public function drawLog()
    {
        global $BACK_PATH;
        $output = '';

        // Init:
        $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
        $this->crawlerController->setAccessMode('gui');
        $this->crawlerController->setID = GeneralUtility::md5int(microtime());

        $csvExport = GeneralUtility::_POST('_csv');
        $this->CSVExport = isset($csvExport);

        // Read URL:
        if (GeneralUtility::_GP('qid_read')) {
            $this->crawlerController->readUrl(intval(GeneralUtility::_GP('qid_read')), true);
        }

        // Look for set ID sent - if it is, we will display contents of that set:
        $showSetId = intval(GeneralUtility::_GP('setID'));

        // Show details:
        if (GeneralUtility::_GP('qid_details')) {

                // Get entry record:
            list($q_entry) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_crawler_queue', 'qid=' . intval(GeneralUtility::_GP('qid_details')));

            // Explode values:
            $resStatus = $this->getResStatus($q_entry);
            $q_entry['parameters'] = unserialize($q_entry['parameters']);
            $q_entry['result_data'] = unserialize($q_entry['result_data']);
            if (is_array($q_entry['result_data'])) {
                $q_entry['result_data']['content'] = unserialize($q_entry['result_data']['content']);

                if (!$this->pObj->MOD_SETTINGS['log_resultLog']) {
                    unset($q_entry['result_data']['content']['log']);
                }
            }

            // Print rudimentary details:
            $output .= '
				<br /><br />
				<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.back') . '" name="_back" />
				<input type="hidden" value="' . $this->pObj->id . '" name="id" />
				<input type="hidden" value="' . $showSetId . '" name="setID" />
				<br />
				Current server time: ' . date('H:i:s', time()) . '<br />' .
                'Status: ' . $resStatus . '<br />' .
                DebugUtility::viewArray($q_entry);
        } else {	// Show list:

            // If either id or set id, show list:
            if ($this->pObj->id || $showSetId) {
                if ($this->pObj->id) {
                    // Drawing tree:
                    $tree = GeneralUtility::makeInstance(PageTreeView::class);
                    $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
                    $tree->init('AND ' . $perms_clause);

                    // Set root row:
                    $HTML = IconUtility::getIconForRecord('pages', $this->pObj->pageinfo);
                    $tree->tree[] = [
                        'row' => $this->pObj->pageinfo,
                        'HTML' => $HTML
                    ];

                    // Get branch beneath:
                    if ($this->pObj->MOD_SETTINGS['depth']) {
                        $tree->getTree($this->pObj->id, $this->pObj->MOD_SETTINGS['depth'], '');
                    }

                    // Traverse page tree:
                    $code = '';
                    $count = 0;
                    foreach ($tree->tree as $data) {
                        $code .= $this->drawLog_addRows(
                                    $data['row'],
                                    $data['HTML'] . BackendUtility::getRecordTitle('pages', $data['row'], true),
                                    intval($this->pObj->MOD_SETTINGS['itemsPerPage'])
                                );
                        if (++$count == 1000) {
                            break;
                        }
                    }
                } else {
                    $code = '';
                    $code .= $this->drawLog_addRows(
                                $showSetId,
                                'Set ID: ' . $showSetId
                            );
                }

                if ($code) {
                    $output .= '
						<br /><br />
						<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.reloadlist') . '" name="_reload" />
						<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.downloadcsv') . '" name="_csv" />
						<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.flushvisiblequeue') . '" name="_flush" onclick="return confirm(\'' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.confirmyouresure') . '\');" />
						<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.flushfullqueue') . '" name="_flush_all" onclick="return confirm(\'' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.confirmyouresure') . '\');" />
						<input type="hidden" value="' . $this->pObj->id . '" name="id" />
						<input type="hidden" value="' . $showSetId . '" name="setID" />
						<br />
						' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.curtime') . ': ' . date('H:i:s', time()) . '
						<br /><br />


						<table class="lrPadding c-list crawlerlog">' .
                            $this->drawLog_printTableHeader() .
                            $code .
                        '</table>';
                }
            } else {	// Otherwise show available sets:
                $setList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                'set_id, count(*) as count_value, scheduled',
                                'tx_crawler_queue',
                                '',
                                'set_id, scheduled',
                                'scheduled DESC'
                            );

                $code = '
					<tr class="bgColor5 tableheader">
						<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.setid') . ':</td>
						<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.count') . 't:</td>
						<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.time') . ':</td>
					</tr>
				';

                $cc = 0;
                foreach ($setList as $set) {
                    $code .= '
						<tr class="bgColor' . ($cc % 2 ? '-20' : '-10') . '">
							<td><a href="' . htmlspecialchars('index.php?setID=' . $set['set_id']) . '">' . $set['set_id'] . '</a></td>
							<td>' . $set['count_value'] . '</td>
							<td>' . BackendUtility::dateTimeAge($set['scheduled']) . '</td>
						</tr>
					';

                    $cc++;
                }

                $output .= '
					<br /><br />
					<table class="lrPadding c-list">' .
                        $code .
                    '</table>';
            }
        }

        if ($this->CSVExport) {
            $this->outputCsvFile();
        }

        // Return output
        return 	$output;
    }

    /**
     * Outputs the CSV file and sets the correct headers
     */
    protected function outputCsvFile()
    {
        if (!count($this->CSVaccu)) {
            $this->addWarningMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:message.canNotExportEmptyQueueToCsvText'));
            return;
        }

        $csvLines = [];

        // Field names:
        reset($this->CSVaccu);
        $fieldNames = array_keys(current($this->CSVaccu));
        $csvLines[] = GeneralUtility::csvValues($fieldNames);

        // Data:
        foreach ($this->CSVaccu as $row) {
            $csvLines[] = GeneralUtility::csvValues($row);
        }

        // Creating output header:
        $mimeType = 'application/octet-stream';
        Header('Content-Type: ' . $mimeType);
        Header('Content-Disposition: attachment; filename=CrawlerLog.csv');

        // Printing the content of the CSV lines:
        echo implode(chr(13) . chr(10), $csvLines);

        // Exits:
        exit;
    }

    /**
     * Create the rows for display of the page tree
     * For each page a number of rows are shown displaying GET variable configuration
     *
     * @param array $pageRow_setId Page row or set-id
     * @param string $titleString Title string
     * @param int $itemsPerPage Items per Page setting
     *
     * @return string HTML <tr> content (one or more)
     */
    public function drawLog_addRows($pageRow_setId, $titleString, $itemsPerPage = 10)
    {

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

        // Get result:
        if (is_array($pageRow_setId)) {
            $res = $this->crawlerController->getLogEntriesForPageId($pageRow_setId['uid'], $this->pObj->MOD_SETTINGS['log_display'], $doFlush, $doFullFlush, intval($itemsPerPage));
        } else {
            $res = $this->crawlerController->getLogEntriesForSetId($pageRow_setId, $this->pObj->MOD_SETTINGS['log_display'], $doFlush, $doFullFlush, intval($itemsPerPage));
        }

        // Init var:
        $colSpan = 9
                + ($this->pObj->MOD_SETTINGS['log_resultLog'] ? -1 : 0)
                + ($this->pObj->MOD_SETTINGS['log_feVars'] ? 3 : 0);

        if (count($res)) {
            // Traverse parameter combinations:
            $c = 0;
            $content = '';
            foreach ($res as $kk => $vv) {

                    // Title column:
                if (!$c) {
                    $titleClm = '<td rowspan="' . count($res) . '">' . $titleString . '</td>';
                } else {
                    $titleClm = '';
                }

                // Result:
                $resLog = $this->getResultLog($vv);

                $resStatus = $this->getResStatus($vv);
                $resFeVars = $this->getResFeVars($vv);

                // Compile row:
                $parameters = unserialize($vv['parameters']);

                // Put data into array:
                $rowData = [];
                if ($this->pObj->MOD_SETTINGS['log_resultLog']) {
                    $rowData['result_log'] = $resLog;
                } else {
                    $rowData['scheduled'] = ($vv['scheduled'] > 0) ? BackendUtility::datetime($vv['scheduled']) : ' ' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.immediate');
                    $rowData['exec_time'] = $vv['exec_time'] ? BackendUtility::datetime($vv['exec_time']) : '-';
                }
                $rowData['result_status'] = GeneralUtility::fixed_lgd_cs($resStatus, 50);
                $rowData['url'] = '<a href="' . htmlspecialchars($parameters['url']) . '" target="_newWIndow">' . htmlspecialchars($parameters['url']) . '</a>';
                $rowData['feUserGroupList'] = $parameters['feUserGroupList'];
                $rowData['procInstructions'] = is_array($parameters['procInstructions']) ? implode('; ', $parameters['procInstructions']) : '';
                $rowData['set_id'] = $vv['set_id'];

                if ($this->pObj->MOD_SETTINGS['log_feVars']) {
                    $rowData['tsfe_id'] = $resFeVars['id'];
                    $rowData['tsfe_gr_list'] = $resFeVars['gr_list'];
                    $rowData['tsfe_no_cache'] = $resFeVars['no_cache'];
                }

                $setId = intval(GeneralUtility::_GP('setID'));
                $refreshIcon = IconUtility::getIcon('actions-system-refresh', Icon::SIZE_SMALL);

                // Put rows together:
                $content .= '
					<tr class="bgColor' . ($c % 2 ? '-20' : '-10') . '">
						' . $titleClm . '
						<td><a href="' . $this->getModuleUrl(['qid_details' => $vv['qid'], 'setID' => $setId]) . '">' . htmlspecialchars($vv['qid']) . '</a></td>
						<td><a href="' . $this->getModuleUrl(['qid_read' => $vv['qid'], 'setID' => $setId]) . '">' . $refreshIcon . '</a></td>';
                foreach ($rowData as $fKey => $value) {
                    if (GeneralUtility::inList('url', $fKey)) {
                        $content .= '
						<td>' . $value . '</td>';
                    } else {
                        $content .= '
						<td>' . nl2br(htmlspecialchars($value)) . '</td>';
                    }
                }
                $content .= '
					</tr>';
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
				<tr class="bgColor-20">
					<td>' . $titleString . '</td>
					<td colspan="' . $colSpan . '"><em>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.noentries') . '</em></td>
				</tr>';
        }

        return $content;
    }

    /**
     * Find Fe vars
     *
     * @param array $row
     * @return array
     */
    public function getResFeVars($row)
    {
        $feVars = [];

        if ($row['result_data']) {
            $resultData = unserialize($row['result_data']);
            $requestResult = unserialize($resultData['content']);
            $feVars = $requestResult['vars'];
        }

        return $feVars;
    }

    /**
     * Create Table header row (log)
     *
     * @return	string		Table header
     */
    public function drawLog_printTableHeader()
    {
        $content = '
			<tr class="bgColor5 tableheader">
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.pagetitle') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.qid') . ':</td>
				<td>&nbsp;</td>' .
                ($this->pObj->MOD_SETTINGS['log_resultLog'] ? '
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.resultlog') . ':</td>' : '
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.scheduledtime') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.runtime') . ':</td>') . '
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.status') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.url') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.groups') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.procinstr') . ':</td>
				<td>' . $GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.setid') . ':</td>' .
                ($this->pObj->MOD_SETTINGS['log_feVars'] ? '
				<td>' . htmlspecialchars('TSFE->id') . '</td>
				<td>' . htmlspecialchars('TSFE->gr_list') . '</td>
				<td>' . htmlspecialchars('TSFE->no_cache') . '</td>' : '') . '
			</tr>';

        return $content;
    }

    /**
     * Extract the log information from the current row and retrive it as formatted string.
     *
     * @param array $resultRow
     *
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

    public function getResStatus($vv)
    {
        if ($vv['result_data']) {
            $requestContent = unserialize($vv['result_data']);
            $requestResult = unserialize($requestContent['content']);
            if (is_array($requestResult)) {
                if (empty($requestResult['errorlog'])) {
                    $resStatus = 'OK';
                } else {
                    $resStatus = implode("\n", $requestResult['errorlog']);
                }
            } else {
                $resStatus = 'Error: ' . substr(preg_replace('/\s+/', ' ', strip_tags($requestContent['content'])), 0, 10000) . '...';
            }
        } else {
            $resStatus = '-';
        }
        return $resStatus;
    }

    /*****************************
     *
     * CLI status display
     *
     *****************************/

    /**
     * This method is used to show an overview about the active an the finished crawling processes
     *
     * @param void
     * @return string
     */
    protected function drawProcessOverviewAction()
    {
        $this->runRefreshHooks();

        global $BACK_PATH;
        $this->makeCrawlerProcessableChecks();

        $crawler = $this->findCrawler();
        try {
            $this->handleProcessOverviewActions();
        } catch (\Exception $e) {
            $this->addErrorMessage($e->getMessage());
        }

        $offset = intval(GeneralUtility::_GP('offset'));
        $perpage = 20;

        $processRepository = new ProcessRepository();
        $queueRepository = new QueueRepository();

        $mode = $this->pObj->MOD_SETTINGS['processListMode'];
        $where = '';
        if ($mode == 'simple') {
            $where = 'active = 1';
        }

        $allProcesses = $processRepository->findAll('ttl', 'DESC', $perpage, $offset, $where);
        $allCount = $processRepository->countAll($where);

        $listView = new ProcessListView();
        $listView->setPageId($this->pObj->id);
        $listView->setIconPath($BACK_PATH . '../typo3conf/ext/crawler/template/process/res/img/');
        $listView->setProcessCollection($allProcesses);
        $listView->setCliPath($this->processManager->getCrawlerCliPath());
        $listView->setIsCrawlerEnabled(!$crawler->getDisabled() && !$this->isErrorDetected);
        $listView->setTotalUnprocessedItemCount($queueRepository->countAllPendingItems());
        $listView->setAssignedUnprocessedItemCount($queueRepository->countAllAssignedPendingItems());
        $listView->setActiveProcessCount($processRepository->countActive());
        $listView->setMaxActiveProcessCount(MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1));
        $listView->setMode($mode);

        $paginationView = new PaginationView();
        $paginationView->setCurrentOffset($offset);
        $paginationView->setPerPage($perpage);
        $paginationView->setTotalItemCount($allCount);

        $output = $listView->render();

        if ($paginationView->getTotalPagesCount() > 1) {
            $output .= ' <br />' . $paginationView->render();
        }

        return $output;
    }

    /**
     * Verify that the crawler is exectuable.
     *
     * @return void
     */
    protected function makeCrawlerProcessableChecks()
    {
        global $LANG;

        if ($this->isCrawlerUserAvailable() === false) {
            $this->addErrorMessage($LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:message.noBeUserAvailable'));
        } elseif ($this->isCrawlerUserNotAdmin() === false) {
            $this->addErrorMessage($LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:message.beUserIsAdmin'));
        }

        if ($this->isPhpForkAvailable() === false) {
            $this->addErrorMessage($LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:message.noPhpForkAvailable'));
        }

        $exitCode = 0;
        $out = [];
        exec(escapeshellcmd($this->extensionSettings['phpPath'] . ' -v'), $out, $exitCode);
        if ($exitCode > 0) {
            $this->addErrorMessage(sprintf($LANG->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:message.phpBinaryNotFound'), htmlspecialchars($this->extensionSettings['phpPath'])));
        }
    }

    /**
     * Indicate that the required PHP method "popen" is
     * available in the system.
     *
     * @return boolean
     */
    protected function isPhpForkAvailable()
    {
        return function_exists('popen');
    }

    /**
     * Indicate that the required be_user "_cli_crawler" is
     * global available in the system.
     *
     * @return boolean
     */
    protected function isCrawlerUserAvailable()
    {
        $isAvailable = false;
        $userArray = BackendUtility::getRecordsByField('be_users', 'username', '_cli_crawler');

        if (is_array($userArray)) {
            $isAvailable = true;
        }

        return $isAvailable;
    }

    /**
     * Indicate that the required be_user "_cli_crawler" is
     * has no admin rights.
     *
     * @return boolean
     */
    protected function isCrawlerUserNotAdmin()
    {
        $isAvailable = false;
        $userArray = BackendUtility::getRecordsByField('be_users', 'username', '_cli_crawler');

        if (is_array($userArray) && $userArray[0]['admin'] == 0) {
            $isAvailable = true;
        }

        return $isAvailable;
    }

    /**
     * Method to handle incomming actions of the process overview
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function handleProcessOverviewActions()
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
                $handle = $this->processManager->startProcess();
                if ($handle === false) {
                    throw new \Exception($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.newprocesserror'));
                }
                $this->addNoticeMessage($GLOBALS['LANG']->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xml:labels.newprocess'));
                break;
        }
    }

    /**
     * Returns the singleton instance of the crawler.
     *
     * @param void
     * @return CrawlerController crawler object
     */
    protected function findCrawler()
    {
        if (!$this->crawlerController instanceof CrawlerController) {
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
     *
     * @return void
     */
    private function addMessage($message, $severity = FlashMessage::OK)
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
     *
     * @return void
     */
    protected function addNoticeMessage($message)
    {
        $this->addMessage($message, FlashMessage::NOTICE);
    }

    /**
     * Add error message to the user interface.
     *
     * @param string The message
     *
     * @return void
     */
    protected function addErrorMessage($message)
    {
        $this->isErrorDetected = true;
        $this->addMessage($message, FlashMessage::ERROR);
    }

    /**
     * Add error message to the user interface.
     *
     * NOTE:
     * This method is basesd on TYPO3 4.3 or higher!
     *
     * @param string The message
     *
     * @return void
     */
    protected function addWarningMessage($message)
    {
        $this->addMessage($message, FlashMessage::WARNING);
    }

    /**
     * Create selector box
     *
     * @param	array		$optArray Options key(value) => label pairs
     * @param	string		$name Selector box name
     * @param	string		$value Selector box value (array for multiple...)
     * @param	boolean		$multiple If set, will draw multiple box.
     *
     * @return	string		HTML select element
     */
    public function selectorBox($optArray, $name, $value, $multiple)
    {
        $options = [];
        foreach ($optArray as $key => $val) {
            $options[] = '
				<option value="' . htmlspecialchars($key) . '"' . ((!$multiple && !strcmp($value, $key)) || ($multiple && in_array($key, (array)$value)) ? ' selected="selected"' : '') . '>' . htmlspecialchars($val) . '</option>';
        }

        $output = '<select name="' . htmlspecialchars($name . ($multiple ? '[]' : '')) . '"' . ($multiple ? ' multiple="multiple" size="' . count($options) . '"' : '') . '>' . implode('', $options) . '</select>';

        return $output;
    }

    /**
     * Activate hooks
     *
     * @return	void
     */
    public function runRefreshHooks()
    {
        $crawlerLib = GeneralUtility::makeInstance(CrawlerController::class);
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['refresh_hooks'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['refresh_hooks'] as $objRef) {
                $hookObj = &GeneralUtility::getUserObj($objRef);
                if (is_object($hookObj)) {
                    $hookObj->crawler_init($crawlerLib);
                }
            }
        }
    }

    /**
     * Returns the URL to the current module, including $_GET['id'].
     *
     * @param array $urlParameters optional parameters to add to the URL
     * @return string
     */
    protected function getModuleUrl(array $urlParameters = [])
    {
        if ($this->pObj->id) {
            $urlParameters = array_merge($urlParameters, [
                'id' => $this->pObj->id
            ]);
        }
        return BackendUtility::getModuleUrl(GeneralUtility::_GP('M'), $urlParameters);
    }
}
