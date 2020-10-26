<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend\RequestForm;

use AOE\Crawler\Backend\Helper\UrlBuilder;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Utility\MessageUtility;
use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

final class LogRequestForm extends AbstractRequestForm implements RequestForm
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
    private $CSVExport;

    /**
     * @var InfoModuleController
     */
    private $infoModuleController;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function __construct(StandaloneView $view, InfoModuleController $infoModuleController)
    {
        $this->view = $view;
        $this->infoModuleController = $infoModuleController;
        $this->jsonCompatibilityConverter = new JsonCompatibilityConverter();
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_crawler_queue');
        // TODO: Implement CSV Writer
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
                $resStatus = $this->getResStatus($q_entry['result_data']);
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
                    $doFullFlush = false;
                } elseif (GeneralUtility::_POST('_flush_all')) {
                    $doFlush = true;
                    $doFullFlush = true;
                } else {
                    $doFlush = false;
                    $doFullFlush = false;
                }
                $itemsPerPage = (int) $this->infoModuleController->MOD_SETTINGS['itemsPerPage'];
                // Traverse page tree:
                $code = '';
                $count = 0;
                foreach ($tree->tree as $data) {
                    // Get result:
                    $logEntriesOfPage = $this->crawlerController->getLogEntriesForPageId(
                        (int) $data['row']['uid'],
                        $this->infoModuleController->MOD_SETTINGS['log_display'],
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

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
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
    private function drawLog_addRows(array $logEntriesOfPage, string $titleString): string
    {
        $colSpan = 9
            + ($this->infoModuleController->MOD_SETTINGS['log_resultLog'] ? -1 : 0)
            + ($this->infoModuleController->MOD_SETTINGS['log_feVars'] ? 3 : 0);

        if (! empty($logEntriesOfPage)) {
            $setId = (int) GeneralUtility::_GP('setID');
            $refreshIcon = $this->getIconFactory()->getIcon('actions-system-refresh', Icon::SIZE_SMALL);
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

                $resultData = $vv['result_data'] ? $this->jsonCompatibilityConverter->convert($vv['result_data']) : [];
                $resStatus = $this->getResStatus($resultData);

                // Compile row:
                $parameters = $this->jsonCompatibilityConverter->convert($vv['parameters']);

                // Put data into array:
                $rowData = [];
                if ($this->infoModuleController->MOD_SETTINGS['log_resultLog']) {
                    $rowData['result_log'] = $resLog;
                } else {
                    $rowData['scheduled'] = ($vv['scheduled'] > 0) ? BackendUtility::datetime($vv['scheduled']) : ' ' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.immediate');
                    $rowData['exec_time'] = $vv['exec_time'] ? BackendUtility::datetime($vv['exec_time']) : '-';
                }
                $rowData['result_status'] = GeneralUtility::fixed_lgd_cs($resStatus, 50);
                $url = htmlspecialchars($parameters['url'] ?? $parameters['alturl'], ENT_QUOTES | ENT_HTML5);
                $rowData['url'] = '<a href="' . $url . '" target="_newWIndow">' . $url . '</a>';
                $rowData['feUserGroupList'] = $parameters['feUserGroupList'] ?: '';
                $rowData['procInstructions'] = is_array($parameters['procInstructions']) ? implode('; ', $parameters['procInstructions']) : '';
                $rowData['set_id'] = (string) $vv['set_id'];

                if ($this->infoModuleController->MOD_SETTINGS['log_feVars']) {
                    $resFeVars = $this->getResFeVars($resultData ?: []);
                    $rowData['tsfe_id'] = $resFeVars['id'] ?: '';
                    $rowData['tsfe_gr_list'] = $resFeVars['gr_list'] ?: '';
                    $rowData['tsfe_no_cache'] = $resFeVars['no_cache'] ?: '';
                }

                $trClass = '';
                $warningIcon = '';
                if ($rowData['exec_time'] !== 0 && $resultData === false) {
                    $trClass = 'class="bg-danger"';
                    $warningIcon = $this->getIconFactory()->getIcon('actions-ban', Icon::SIZE_SMALL);
                }

                // Put rows together:
                $content .= '
                    <tr ' . $trClass . ' >
                        ' . $titleClm . '
                        <td><a href="' . UrlBuilder::getInfoModuleUrl(['qid_details' => $vv['qid'], 'setID' => $setId]) . '">' . htmlspecialchars((string) $vv['qid']) . '</a></td>
                        <td><a href="' . UrlBuilder::getInfoModuleUrl(['qid_read' => $vv['qid'], 'setID' => $setId]) . '">' . $refreshIcon . '</a>&nbsp;&nbsp;' . $warningIcon . '</td>';
                foreach ($rowData as $fKey => $value) {
                    if ($fKey === 'url') {
                        $content .= '<td>' . $value . '</td>';
                    } else {
                        $content .= '<td>' . nl2br(htmlspecialchars(strval($value))) . '</td>';
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
                    <td colspan="' . $colSpan . '"><em>' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noentries') . '</em></td>
                </tr>';
        }

        return $content;
    }

    /**
     * Extract the log information from the current row and retrieve it as formatted string.
     *
     * @param array $resultRow
     * @return string
     */
    private function getResultLog($resultRow)
    {
        $content = '';
        if (is_array($resultRow) && array_key_exists('result_data', $resultRow)) {
            $requestContent = $this->jsonCompatibilityConverter->convert($resultRow['result_data']) ?: ['content' => ''];
            if (! array_key_exists('content', $requestContent)) {
                return $content;
            }
            $requestResult = $this->jsonCompatibilityConverter->convert($requestContent['content']);

            if (is_array($requestResult) && array_key_exists('log', $requestResult)) {
                $content = implode(chr(10), $requestResult['log']);
            }
        }
        return $content;
    }

    private function getResStatus($requestContent): string
    {
        if (empty($requestContent)) {
            return '-';
        }
        if (! array_key_exists('content', $requestContent)) {
            return 'Content index does not exists in requestContent array';
        }

        $requestResult = $this->jsonCompatibilityConverter->convert($requestContent['content']);
        if (is_array($requestResult)) {
            if (empty($requestResult['errorlog'])) {
                return 'OK';
            }
            return implode("\n", $requestResult['errorlog']);
        }

        if (is_bool($requestResult)) {
            return 'Error - no info, sorry!';
        }

        return 'Error: ' . substr(preg_replace('/\s+/', ' ', strip_tags($requestResult)), 0, 10000) . '...';
    }

    /**
     * Find Fe vars
     */
    private function getResFeVars(array $resultData): array
    {
        if (empty($resultData)) {
            return [];
        }
        $requestResult = $this->jsonCompatibilityConverter->convert($resultData['content']);
        return $requestResult['vars'] ?? [];
    }
}
