<?php
declare(strict_types=1);

namespace AOE\Crawler\Backend\RequestForm;

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Csv\CsvWriter;
use AOE\Crawler\Utility\MessageUtility;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class LogRequestForm implements RequestForm
{
    /** @var StandaloneView */
    private $view;

    public function __construct(StandaloneView $view)
    {
        $this->view = $view;
        // TODO: Implement CSV Writer

    }

    public function render($id, string $currentValue, array $menuItems): string
    {
        $quiPart = GeneralUtility::_GP('qid_details') ? '&qid_details=' . (int)GeneralUtility::_GP('qid_details') : '';
        $setId = (int)GeneralUtility::_GP('setID');

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
        if (empty($this->id)) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noPageSelected'));
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
                $q_entry['parameters'] = $this->jsonCompatibilityConverter->convert($q_entry['parameters']);
                $q_entry['result_data'] = $this->jsonCompatibilityConverter->convert($q_entry['result_data']);
                $resStatus = $this->getResStatus($q_entry['result_data']);
                if (is_array($q_entry['result_data'])) {
                    $q_entry['result_data']['content'] = $this->jsonCompatibilityConverter->convert($q_entry['result_data']['content']);
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

    /**
     * @return LanguageService
     */
    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getDisplayLogFilterHtml(int $setId): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.display') . ': ' . BackendUtility::getFuncMenu(
                $this->id,
                'SET[log_display]',
                $this->pObj->MOD_SETTINGS['log_display'],
                $this->pObj->MOD_MENU['log_display'],
                'index.php',
                '&setID=' . $setId
            );
    }

    private function getItemsPerPageDropDownHtml(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage') . ': ' .
            BackendUtility::getFuncMenu(
                $this->id,
                'SET[itemsPerPage]',
                $this->pObj->MOD_SETTINGS['itemsPerPage'],
                $this->pObj->MOD_MENU['itemsPerPage']
            );
    }

    private function getShowResultLogCheckBoxHtml(int $setId, string $quiPart): string
    {
        return BackendUtility::getFuncCheck(
                $this->id,
                'SET[log_resultLog]',
                $this->pObj->MOD_SETTINGS['log_resultLog'],
                'index.php',
                '&setID=' . $setId . $quiPart
            ) . '&nbsp;' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showresultlog');
    }

    private function getShowFeVarsCheckBoxHtml(int $setId, string $quiPart): string
    {
        return BackendUtility::getFuncCheck(
                $this->id,
                'SET[log_feVars]',
                $this->pObj->MOD_SETTINGS['log_feVars'],
                'index.php',
                '&setID=' . $setId . $quiPart
            ) . '&nbsp;' . $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showfevars');
    }
}
