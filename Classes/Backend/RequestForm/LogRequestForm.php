<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend\RequestForm;

use AOE\Crawler\Backend\Helper\ResultHandler;
use AOE\Crawler\Backend\Helper\UrlBuilder;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Value\QueueFilter;
use AOE\Crawler\Value\QueueLogEntry;
use AOE\Crawler\Writer\FileWriter\CsvWriter\CrawlerCsvWriter;
use AOE\Crawler\Writer\FileWriter\CsvWriter\CsvWriterInterface;
use Codeception\Util\Debug;
use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

final class LogRequestForm extends AbstractRequestForm implements RequestFormInterface
{
    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var JsonCompatibilityConverter
     */
    private $jsonCompatibilityConverter;

    /**
     * @var int
     */
    private $pageId;

    /**
     * @var bool
     */
    private $CSVExport = false;

    /**
     * @var InfoModuleController
     */
    private $infoModuleController;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var CsvWriterInterface
     */
    private $csvWriter;

    /**
     * @var QueueRepository
     */
    private $queueRepository;

    /**
     * @var array
     */
    private $CSVaccu = [];

    public function __construct(StandaloneView $view, InfoModuleController $infoModuleController, array $extensionSettings)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->view = $view;
        $this->infoModuleController = $infoModuleController;
        $this->jsonCompatibilityConverter = new JsonCompatibilityConverter();
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_crawler_queue');
        $this->csvWriter = new CrawlerCsvWriter();
        $this->extensionSettings = $extensionSettings;
        $this->queueRepository = $objectManager->get(QueueRepository::class);
    }

    public function render($id, string $currentValue, array $menuItems): string
    {
        $quiPart = GeneralUtility::_GP('qid_details') ? '&qid_details=' . (int) GeneralUtility::_GP('qid_details') : '';
        $setId = (int) GeneralUtility::_GP('setID');
        $this->pageId = $id;

        return $this->getDepthDropDownHtml($id, $currentValue, $menuItems)
            . $this->showLogAction($setId, $quiPart);
    }

    private function getDepthDropDownHtml($id, string $currentValue, array $menuItems): string
    {
        return BackendUtility::getFuncMenu(
            $id,
            'SET[depth]',
            $currentValue,
            $menuItems
        );
    }

    /*******************************
     *
     * Shows log of indexed URLs
     *
     ******************************/

    /**
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    private function showLogAction(int $setId, string $quiPath): string
    {
        $this->view->setTemplate('ShowLog');
        if (empty($this->pageId)) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noPageSelected'));
        } else {
            $this->findCrawler()->setAccessMode('gui');
            $this->findCrawler()->setID = GeneralUtility::md5int(microtime());

            $csvExport = GeneralUtility::_POST('_csv');
            $this->CSVExport = isset($csvExport);

            // Read URL:
            if (GeneralUtility::_GP('qid_read')) {
                $this->findCrawler()->readUrl((int) GeneralUtility::_GP('qid_read'), true);
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
                $q_entry['parameters'] = $this->jsonCompatibilityConverter->convert($q_entry['parameters']);
                $q_entry['result_data'] = $this->jsonCompatibilityConverter->convert($q_entry['result_data']);
                $resStatus = ResultHandler::getResStatus($q_entry['result_data']);
                if (is_array($q_entry['result_data'])) {
                    $q_entry['result_data']['content'] = $this->jsonCompatibilityConverter->convert($q_entry['result_data']['content']);
                    if (! $this->infoModuleController->MOD_SETTINGS['log_resultLog']) {
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
                    $this->pageId,
                    $perms_clause
                );
                $HTML = $this->getIconFactory()->getIconForRecord('pages', $pageinfo, Icon::SIZE_SMALL)->render();
                $tree->tree[] = [
                    'row' => $pageinfo,
                    'HTML' => $HTML,
                ];

                // Get branch beneath:
                if ($this->infoModuleController->MOD_SETTINGS['depth']) {
                    $tree->getTree($this->pageId, $this->infoModuleController->MOD_SETTINGS['depth']);
                }

                // If Flush button is pressed, flush tables instead of selecting entries:
                if (GeneralUtility::_POST('_flush')) {
                    $doFlush = true;
                } elseif (GeneralUtility::_POST('_flush_all')) {
                    $doFlush = true;
                    $this->infoModuleController->MOD_SETTINGS['log_display'] = 'all';
                } else {
                    $doFlush = false;
                }
                $itemsPerPage = (int) $this->infoModuleController->MOD_SETTINGS['itemsPerPage'];
                $queueFilter = new QueueFilter($this->infoModuleController->MOD_SETTINGS['log_display']);

                if ($doFlush) {
                    $this->queueRepository->flushQueue($queueFilter);
                }

                // Traverse page tree:
                $code = '';
                $count = 0;
                $logEntries = [];
                foreach ($tree->tree as $data) {
                    // Get result:
                    $logEntriesOfPage = $this->queueRepository->getQueueEntriesForPageId(
                        (int) $data['row']['uid'],
                        $itemsPerPage,
                        $queueFilter
                    );

                    //$logEntries[] = $logEntriesOfPage;

                    $logEntries[] = $this->drawLog_addRows(
                        $logEntriesOfPage,
                        $data['HTML'] . BackendUtility::getRecordTitle('pages', $data['row'], true)
                    );
                    if (++$count === 1000) {
                        break;
                    }
                }

                $this->view->assign('code', $code);
                $this->view->assign('logEntries', $logEntries);
            }

            if ($this->CSVExport) {
                $this->outputCsvFile();
            }
        }
        $this->view->assign('showResultLog', (bool) $this->infoModuleController->MOD_SETTINGS['log_resultLog']);
        $this->view->assign('showFeVars', (bool) $this->infoModuleController->MOD_SETTINGS['log_feVars']);
        $this->view->assign('displayActions', 1);
        $this->view->assign('displayLogFilterHtml', $this->getDisplayLogFilterHtml($setId));
        $this->view->assign('itemPerPageHtml', $this->getItemsPerPageDropDownHtml());
        $this->view->assign('showResultLogHtml', $this->getShowResultLogCheckBoxHtml($setId, $quiPath));
        $this->view->assign('showFeVarsHtml', $this->getShowFeVarsCheckBoxHtml($setId, $quiPath));
        return $this->view->render();
    }

    /**
     * Outputs the CSV file and sets the correct headers
     */
    private function outputCsvFile(): void
    {
        if (! count($this->CSVaccu)) {
            MessageUtility::addWarningMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:message.canNotExportEmptyQueueToCsvText'));
            return;
        }

        $csvString = $this->csvWriter->arrayToCsv($this->CSVaccu);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=CrawlerLog.csv');
        echo $csvString;

        exit;
    }

    private function getIconFactory(): IconFactory
    {
        return GeneralUtility::makeInstance(IconFactory::class);
    }

    private function getDisplayLogFilterHtml(int $setId): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.display') . ': ' . BackendUtility::getFuncMenu(
                $this->pageId,
                'SET[log_display]',
                $this->infoModuleController->MOD_SETTINGS['log_display'],
                $this->infoModuleController->MOD_MENU['log_display'],
                'index.php',
                '&setID=' . $setId
            );
    }

    private function getItemsPerPageDropDownHtml(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage') . ': ' .
            BackendUtility::getFuncMenu(
                $this->pageId,
                'SET[itemsPerPage]',
                $this->infoModuleController->MOD_SETTINGS['itemsPerPage'],
                $this->infoModuleController->MOD_MENU['itemsPerPage']
            );
    }

    private function getShowResultLogCheckBoxHtml(int $setId, string $quiPart): string
    {
        return BackendUtility::getFuncCheck(
                $this->pageId,
                'SET[log_resultLog]',
                $this->infoModuleController->MOD_SETTINGS['log_resultLog'],
                'index.php',
                '&setID=' . $setId . $quiPart
            ) . '&nbsp;' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showresultlog');
    }

    private function getShowFeVarsCheckBoxHtml(int $setId, string $quiPart): string
    {
        return BackendUtility::getFuncCheck(
                $this->pageId,
                'SET[log_feVars]',
                $this->infoModuleController->MOD_SETTINGS['log_feVars'],
                'index.php',
                '&setID=' . $setId . $quiPart
            ) . '&nbsp;' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showfevars');
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
    private function drawLog_addRows(array $logEntriesOfPage, string $titleString): array
    {
        $contentArray = [];
        $contentArray['title'] = $titleString;
        $contentArray['titleRowSpan'] = 1;
        $contentArray['colSpan'] =  9
            + ($this->infoModuleController->MOD_SETTINGS['log_resultLog'] ? -1 : 0)
            + ($this->infoModuleController->MOD_SETTINGS['log_feVars'] ? 3 : 0);

        if (! empty($logEntriesOfPage)) {
            $setId = (int) GeneralUtility::_GP('setID');
            $refreshIcon = $this->getIconFactory()->getIcon('actions-system-refresh', Icon::SIZE_SMALL);
            // Traverse parameter combinations:
            $c = 0;
            foreach ($logEntriesOfPage as $vv) {
                // Title column:
                if (! $c) {
                    $contentArray['titleRowSpan'] = count($logEntriesOfPage);
                    $contentArray['title'] = $titleString;
                } else {
                    $contentArray['title'] = '';
                }

                $execTime = $vv['exec_time'] ? BackendUtility::datetime($vv['exec_time']) : '-';

                // Result:
                $resLog = ResultHandler::getResultLog($vv);

                $resultData = $vv['result_data'] ? $this->jsonCompatibilityConverter->convert($vv['result_data']) : [];
                $resStatus = ResultHandler::getResStatus($resultData);

                // Compile row:
                $parameters = $this->jsonCompatibilityConverter->convert($vv['parameters']);

                // Put data into array:
                $rowData = [];
                if ($this->infoModuleController->MOD_SETTINGS['log_resultLog']) {
                    $rowData['result_log'] = $resLog;
                } else {
                    $rowData['scheduled'] = ($vv['scheduled'] > 0) ? BackendUtility::datetime($vv['scheduled']) : ' ' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.immediate');
                    $rowData['exec_time'] = $execTime;
                }
                $rowData['result_status'] = GeneralUtility::fixed_lgd_cs($resStatus, 50);
                $url = htmlspecialchars($parameters['url'] ?? $parameters['alturl'], ENT_QUOTES | ENT_HTML5);
                $rowData['url'] = '<a href="' . $url . '" target="_newWIndow">' . $url . '</a>';
                $rowData['feUserGroupList'] = $parameters['feUserGroupList'] ?: '';
                $rowData['procInstructions'] = is_array($parameters['procInstructions']) ? implode('; ', $parameters['procInstructions']) : '';
                $rowData['set_id'] = (string) $vv['set_id'];

                if ($this->infoModuleController->MOD_SETTINGS['log_feVars']) {
                    $resFeVars = ResultHandler::getResFeVars($resultData ?: []);
                    $rowData['tsfe_id'] = $resFeVars['id'] ?: '';
                    $rowData['tsfe_gr_list'] = $resFeVars['gr_list'] ?: '';
                    $rowData['tsfe_no_cache'] = $resFeVars['no_cache'] ?: '';
                }

                $trClass = '';
                $warningIcon = '';
                if (str_contains($resStatus, 'Error:')) {
                    $trClass = 'class="bg-danger"';
                    $warningIcon = $this->getIconFactory()->getIcon('actions-ban', Icon::SIZE_SMALL);
                }

                // Put rows together:
                $contentArray['trClass'] = $trClass;
                $contentArray['qid'] = [
                    'link' => UrlBuilder::getInfoModuleUrl(['qid_details' => $vv['qid'], 'setID' => $setId]),
                    'link-text' => htmlspecialchars((string) $vv['qid'], ENT_QUOTES | ENT_HTML5),
                ];
                $contentArray['refresh'] = [
                    'link' => UrlBuilder::getInfoModuleUrl(['qid_read' => $vv['qid'], 'setID' => $setId]),
                    'link-text' => $refreshIcon,
                    'warning' => $warningIcon,
                ];

                foreach ($rowData as $fKey => $value) {
                    if ($fKey === 'url') {
                        $contentArray['columns'][$fKey] = $value;
                    } else {
                        $contentArray['columns'][$fKey] = nl2br(htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5));
                    }
                }
                $c++;

                if ($this->CSVExport) {
                    // Only for CSV (adding qid and scheduled/exec_time if needed):
                    $csvExport['scheduled'] = BackendUtility::datetime($vv['scheduled']);
                    $csvExport['exec_time'] = $vv['exec_time'] ? BackendUtility::datetime($vv['exec_time']) : '-';
                    $csvExport['result_status'] = $contentArray['columns']['result_status'];
                    $csvExport['url'] = $contentArray['columns']['url'];
                    $csvExport['feUserGroupList'] = $contentArray['columns']['feUserGroupList'];
                    $csvExport['procInstructions'] = $contentArray['columns']['procInstructions'];
                    $csvExport['set_id'] = $contentArray['columns']['set_id'];
                    $csvExport['result_log'] = str_replace(chr(10), '// ', $resLog);
                    $csvExport['qid'] = $vv['qid'];
                    $this->CSVaccu[] = $csvExport;
                }
            }
        } else {
            $contentArray['noEntries'] = $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noentries');
        }

        return $contentArray;
    }
}
