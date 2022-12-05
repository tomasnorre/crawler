<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend;

use AOE\Crawler\Backend\Helper\ResultHandler;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
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
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendModuleCrawlerLogController extends AbstractBackendModuleController implements BackendModuleControllerInterface
{
    private QueryBuilder $queryBuilder;
    private bool $CSVExport = false;
    private array $CSVaccu = [];

    public function __construct(
        private QueueRepository $queueRepository,
        private CsvWriterInterface $csvWriter,
        private JsonCompatibilityConverter $jsonCompatibilityConverter
    )
    {
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
            $pageinfo = BackendUtility::readPageAccess($this->pageId, $perms_clause);

            if (is_array($pageinfo)) {
                $HTML = $this->getIconFactory()->getIconForRecord('pages', $pageinfo, Icon::SIZE_SMALL)->render();
                $tree->tree[] = [
                    'row' => $pageinfo,
                    'HTML' => $HTML,
                ];
            }

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
            $queueFilter = new QueueFilter($this->infoModuleController->MOD_SETTINGS['log_display'] ?? 'all');

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
            'showResultLog' => (bool)isset($this->infoModuleController->MOD_SETTINGS['log_resultLog']) ? $this->infoModuleController->MOD_SETTINGS['log_resultLog'] : false,
            'showFeVars' => (bool)isset($this->infoModuleController->MOD_SETTINGS['log_feVars']) ? $this->infoModuleController->MOD_SETTINGS['log_feVars'] : false,
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
                'SET[log_display]',
                $this->infoModuleController->MOD_SETTINGS['log_display'],
                $this->infoModuleController->MOD_MENU['log_display'],
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
                'SET[itemsPerPage]',
                $this->infoModuleController->MOD_SETTINGS['itemsPerPage'],
                $this->infoModuleController->MOD_MENU['itemsPerPage']
            );
    }

    private function getShowResultLogCheckBoxHtml(int $setId, string $quiPart): string
    {
        $currentValue = $this->infoModuleController->MOD_SETTINGS['log_resultLog'] ?? '';

        return BackendUtility::getFuncCheck(
                $this->pageUid,
                'SET[log_resultLog]',
                $currentValue,
                'index.php',
                '&setID=' . $setId . $quiPart
            ) . '&nbsp;' . $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showresultlog'
            );
    }

    private function getShowFeVarsCheckBoxHtml(int $setId, string $quiPart): string
    {
        $currentValue = $this->infoModuleController->MOD_SETTINGS['log_feVars'] ?? '';
        return BackendUtility::getFuncCheck(
                $this->pageUid,
                'SET[log_feVars]',
                $currentValue,
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
            if (!$this->infoModuleController->MOD_SETTINGS['log_resultLog']) {
                if (is_array($q_entry['result_data']['content'])) {
                    unset($q_entry['result_data']['content']['log']);
                }
            }
        }
        return array($q_entry, $resStatus);
    }
}
