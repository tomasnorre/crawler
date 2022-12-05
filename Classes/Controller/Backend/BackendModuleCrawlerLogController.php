<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend;

use AOE\Crawler\Backend\Helper\ResultHandler;
use AOE\Crawler\Backend\Helper\UrlBuilder;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Domain\Model\BackendModuleSettings;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Value\QueueFilter;
use AOE\Crawler\Writer\FileWriter\CsvWriter\CsvWriterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendModuleCrawlerLogController extends AbstractBackendModuleController implements BackendModuleControllerInterface
{
    private const BACKEND_MODULE='web_site_crawler_log';

    private QueryBuilder $queryBuilder;
    private bool $CSVExport = false;
    private array $CSVaccu = [];
    private array $backendModuleMenu = [];

    public function __construct(
        public BackendModuleSettings $backendModuleSettings,
        private QueueRepository $queueRepository,
        private CsvWriterInterface $csvWriter,
        private JsonCompatibilityConverter $jsonCompatibilityConverter,
        private IconFactory $iconFactory,
    ){
        $this->backendModuleMenu = $this->getModuleMenu();
    }

    #[Required]
    public function setQueryBuilder(): void
    {
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            QueueRepository::TABLE_NAME
        );
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageUid = (int)($request->getQueryParams()['id'] ?? -1);
        $this->moduleTemplate = $this->setupView($request, $this->pageUid);
        $this->moduleTemplate = $this->moduleTemplate->makeDocHeaderModuleMenu(['id' => $request->getQueryParams()['id'] ?? -1]);

        if (!$this->pageUid) {
            $this->isErrorDetected = true;
            $this->moduleTemplate->assign('noPageSelected', true);
            return $this->moduleTemplate->renderResponse('Backend/ShowLog');
        }
        $this->moduleTemplate = $this->assignValues();

        return $this->moduleTemplate->renderResponse('Backend/ShowLog');
    }

    private function assignValues(): ModuleTemplate
    {
        $setId = (int)GeneralUtility::_GP('setID');
        $quiPath = GeneralUtility::_GP('qid_details') ? '&qid_details=' . (int)GeneralUtility::_GP('qid_details') : '';
        $queueId = GeneralUtility::_GP('qid_details');

        if(GeneralUtility::_GP('ShowResultLog') !== null) {
            $this->backendModuleSettings->setShowResultLog((bool)GeneralUtility::_GP('ShowResultLog'));
        }
        if(GeneralUtility::_GP('ShowFeVars') !== null) {
            $this->backendModuleSettings->setShowFeLog((bool)GeneralUtility::_GP('ShowFeVars'));
        }


        // Look for set ID sent - if it is, we will display contents of that set:
        $showSetId = (int)GeneralUtility::_GP('setID');

        if ($queueId) {
            // Get entry record:
            [$q_entry, $resStatus] = $this->getQueueEntry($queueId);
            $this->moduleTemplate->assignMultiple([
                'queueStatus' => $resStatus,
                'queueDetails' => DebugUtility::viewArray($q_entry),
            ]);

        } else {
            // Show list
            // Drawing tree:
            $tree = GeneralUtility::makeInstance(PageTreeView::class);
            $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
            $tree->init('AND ' . $perms_clause);

            // Set root row:
            $pageinfo = BackendUtility::readPageAccess($this->pageUid, $perms_clause);

            if (is_array($pageinfo)) {
                $HTML = $this->iconFactory->getIconForRecord('pages', $pageinfo, Icon::SIZE_SMALL)->render();
                $tree->tree[] = [
                    'row' => $pageinfo,
                    'HTML' => $HTML,
                ];
            }

            // Get branch beneath:
            if ($this->backendModuleSettings->getDepth()) {
                $tree->getTree($this->pageUid, $this->backendModuleSettings->getDepth());
            }

            // If Flush button is pressed, flush tables instead of selecting entries:
            if (GeneralUtility::_POST('_flush')) {
                $doFlush = true;
            } elseif (GeneralUtility::_POST('_flush_all')) {
                $doFlush = true;
                $this->backendModuleSettings->setLogDisplay('all');
            } else {
                $doFlush = false;
            }
            $itemsPerPage = $this->backendModuleSettings->getItemsPerPage();
            $queueFilter = new QueueFilter($this->backendModuleSettings->getLogDisplay());

            if ($doFlush) {
                $this->queueRepository->flushQueue($queueFilter);
            }

            // Traverse page tree:
            $count = 0;
            $logEntriesPerPage = [];
            foreach ($tree->tree as $data) {
                $logEntriesOfPage = $this->queueRepository->getQueueEntriesForPageId(
                    (int) $data['row']['uid'],
                    $itemsPerPage,
                    $queueFilter
                );

                $logEntriesPerPage[] = $this->drawLog_addRows(
                    $logEntriesOfPage,
                    $data['HTML'] . BackendUtility::getRecordTitle('pages', $data['row'], true)
                );
                if (++$count === 1000) {
                    break;
                }
            }

            $this->moduleTemplate->assign('logEntriesPerPage', $logEntriesPerPage);
        }

        if ($this->CSVExport) {
            $this->outputCsvFile();
        }


        return $this->moduleTemplate->assignMultiple([
            'queueId' => $queueId,
            'setId' => $showSetId,
            'noPageSelected' => false,
            'logEntriesPerPage' => $logEntriesPerPage,
            'showResultLog' => $this->backendModuleSettings->isShowResultLog(),
            'showFeVars' => $this->backendModuleSettings->isShowFeLog(),
            'displayActions' => 1,
            'displayLogFilterHtml' => $this->getDisplayLogFilterHtml($setId),
            'itemPerPageHtml' => $this->getItemsPerPageDropDownHtml(),
            'showResultLogHtml' => $this->getShowResultLogCheckBoxHtml($setId, $quiPath),
            'showFeVarsHtml' => $this->getShowFeVarsCheckBoxHtml($setId, $quiPath),
        ]);
    }

    private function getDisplayLogFilterHtml(int $setId): string
    {
        return $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.display'
            ) . ': ' . BackendUtility::getFuncMenu(
                $this->pageUid,
                'logDisplay',
                $this->backendModuleSettings->getLogDisplay(),
                $this->backendModuleMenu['log_display'],
                'index.php',
                '&setID=' . $setId
            );
    }

    private function getItemsPerPageDropDownHtml(): string
    {
        return $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage'
            ) . ': ' .
            BackendUtility::getFuncMenu(
                $this->pageUid,
                'itemsPerPage',
                $this->backendModuleSettings->getItemsPerPage(),
                $this->backendModuleMenu['itemsPerPage']
            );
    }

    private function getShowResultLogCheckBoxHtml(int $setId, string $quiPart): string
    {
        return BackendUtility::getFuncCheck(
                $this->pageUid,
                'ShowResultLog',
                $this->backendModuleSettings->isShowResultLog(),
                'index.php',
                '&setID=' . $setId . $quiPart
            ) . '&nbsp;' . $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showresultlog'
            );
    }

    private function getShowFeVarsCheckBoxHtml(int $setId, string $quiPart): string
    {
        return BackendUtility::getFuncCheck(
                $this->pageUid,
                'ShowFeVars',
                $this->backendModuleSettings->isShowFeLog(),
                'index.php',
                '&setID=' . $setId . $quiPart
            ) . '&nbsp;' . $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showfevars'
            );
    }

    /**
     * @param mixed $queueId
     * @return array
     */
    public function getQueueEntry(mixed $queueId): array
    {
        $q_entry = $this->queryBuilder
            ->from(QueueRepository::TABLE_NAME)
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
            $q_entry['result_data']['content'] = $this->jsonCompatibilityConverter->convert(
                $q_entry['result_data']['content']
            );
            if (!$this->backendModuleSettings->isShowResultLog()) {
                if (is_array($q_entry['result_data']['content'])) {
                    unset($q_entry['result_data']['content']['log']);
                }
            }
        }
        return array($q_entry, $resStatus);
    }

    /**
     * Create the rows for display of the page tree
     * For each page a number of rows are shown displaying GET variable configuration
     *
     * @param array $logEntriesOfPage Log items of one page
     * @param string $titleString Title string
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     *
     * @psalm-return non-empty-list<array{titleRowSpan: positive-int, colSpan: int, title: string, noEntries?: string, trClass?: string, qid?: array{link: \TYPO3\CMS\Core\Http\Uri, link-text: string}, refresh?: array{link: \TYPO3\CMS\Core\Http\Uri, link-text: Icon, warning: Icon|string}, columns?: array{url: mixed|string, scheduled: string, exec_time: string, result_log: string, result_status: string, feUserGroupList: string, procInstructions: string, set_id: string, tsfe_id: string, tsfe_gr_list: string}}>
     */
    private function drawLog_addRows(array $logEntriesOfPage, string $titleString): array
    {
        $resultArray = [];
        $contentArray = [];

        $contentArray['titleRowSpan'] = 1;
        $contentArray['colSpan'] = 9
            + ($this->backendModuleSettings->isShowResultLog() ? -1 : 0)
            + ($this->backendModuleSettings->isShowFeLog() ? 3 : 0);

        if (! empty($logEntriesOfPage)) {
            $setId = (int) GeneralUtility::_GP('setID');
            $refreshIcon = $this->iconFactory->getIcon('actions-system-refresh', Icon::SIZE_SMALL);
            // Traverse parameter combinations:
            $firstIteration = true;
            foreach ($logEntriesOfPage as $vv) {
                // Title column:
                if ($firstIteration) {
                    $contentArray['titleRowSpan'] = count($logEntriesOfPage);
                    $contentArray['title'] = $titleString;
                } else {
                    $contentArray['title'] = '';
                    $contentArray['titleRowSpan'] = 1;
                }

                $firstIteration = false;
                $execTime = $vv['exec_time'] ? BackendUtility::datetime($vv['exec_time']) : '-';

                // Result:
                $resLog = ResultHandler::getResultLog($vv);

                $resultData = $vv['result_data'] ? $this->jsonCompatibilityConverter->convert($vv['result_data']) : [];
                $resStatus = ResultHandler::getResStatus($resultData);

                // Compile row:
                $parameters = $this->jsonCompatibilityConverter->convert($vv['parameters']);

                // Put data into array:
                $rowData = [];
                if ($this->backendModuleSettings->isShowResultLog()) {
                    $rowData['result_log'] = $resLog;
                } else {
                    $rowData['scheduled'] = ($vv['scheduled'] > 0) ? BackendUtility::datetime($vv['scheduled']) : '-';
                    $rowData['exec_time'] = $execTime;
                }
                $rowData['result_status'] = GeneralUtility::fixed_lgd_cs($resStatus, 50);
                $url = htmlspecialchars((string) ($parameters['url'] ?? $parameters['alturl']), ENT_QUOTES | ENT_HTML5);
                $rowData['url'] = '<a href="' . $url . '" target="_newWIndow">' . $url . '</a>';
                $rowData['feUserGroupList'] = $parameters['feUserGroupList'] ?? '';
                $rowData['procInstructions'] = is_array($parameters['procInstructions']) ? implode(
                    '; ',
                    $parameters['procInstructions']
                ) : '';
                $rowData['set_id'] = (string) $vv['set_id'];

                if ($this->backendModuleSettings->isShowFeLog()) {
                    $resFeVars = ResultHandler::getResFeVars($resultData ?: []);
                    $rowData['tsfe_id'] = $resFeVars['id'] ?? '';
                    $rowData['tsfe_gr_list'] = $resFeVars['gr_list'] ?? '';
                }

                $trClass = '';
                $warningIcon = '';
                if (str_contains($resStatus, 'Error:')) {
                    $trClass = 'bg-danger';
                    $warningIcon = $this->iconFactory->getIcon('actions-ban', Icon::SIZE_SMALL);
                }

                // Put rows together:
                $contentArray['trClass'] = $trClass;
                $contentArray['qid'] = [
                    'link' => UrlBuilder::getBackendModuleUrl(['qid_details' => $vv['qid'], 'setID' => $setId], self::BACKEND_MODULE),
                    'link-text' => htmlspecialchars((string) $vv['qid'], ENT_QUOTES | ENT_HTML5),
                ];
                $contentArray['refresh'] = [
                    'link' => UrlBuilder::getBackendModuleUrl(['qid_read' => $vv['qid'], 'setID' => $setId], self::BACKEND_MODULE),
                    'link-text' => $refreshIcon,
                    'warning' => $warningIcon,
                ];

                foreach ($rowData as $fKey => $value) {
                    if ($fKey === 'url') {
                        $contentArray['columns'][$fKey] = $value;
                    } else {
                        $contentArray['columns'][$fKey] = nl2br(
                            htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5)
                        );
                    }
                }

                $resultArray[] = $contentArray;

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
            $contentArray['title'] = $titleString;
            $contentArray['noEntries'] = $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noentries'
            );

            $resultArray[] = $contentArray;
        }

        return $resultArray;
    }
}
