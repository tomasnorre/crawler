<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller\Backend;

/*
 * (c) 2022-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Controller\Backend\Helper\ResultHandler;
use AOE\Crawler\Controller\Backend\Helper\UrlBuilder;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Service\BackendModuleLogService;
use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Value\QueueFilter;
use AOE\Crawler\Writer\FileWriter\CsvWriter\CsvWriterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v12.0.0
 */
final class BackendModuleCrawlerLogController extends AbstractBackendModuleController implements BackendModuleControllerInterface
{
    public const BACKEND_MODULE = 'web_site_crawler_log';

    private QueryBuilder $queryBuilder;
    private bool $CSVExport = false;
    private array $CSVaccu = [];
    private readonly array $backendModuleMenu;
    private int $setId;
    private string $quiPath;
    private string $logDisplay;
    private int $itemsPerPage;
    private string $showResultLog;
    private string $showFeVars;
    private int $showSetId;
    private string $logDepth;
    /**
     * @var mixed|string|null
     */
    private mixed $queueId;

    public function __construct(
        private readonly QueueRepository $queueRepository,
        private readonly CsvWriterInterface $csvWriter,
        private readonly JsonCompatibilityConverter $jsonCompatibilityConverter,
        private readonly IconFactory $iconFactory,
        private readonly CrawlerController $crawlerController,
        private readonly BackendModuleLogService $backendModuleLogService,
    ) {
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
        $this->setPropertiesBasedOnPostVars($request);
        $this->moduleTemplate = $this->setupView($request, $this->pageUid);
        $this->moduleTemplate = $this->moduleTemplate->makeDocHeaderModuleMenu(['id' => $this->pageUid]);

        if (!$this->pageUid) {
            $this->isErrorDetected = true;
            $this->moduleTemplate->assign('noPageSelected', true);
            return $this->moduleTemplate->renderResponse('Backend/ShowLog');
        }
        $this->moduleTemplate = $this->assignValues();
        return $this->moduleTemplate->renderResponse('Backend/ShowLog');
    }

    private function getQueueEntry(mixed $queueId): array
    {
        $q_entry = $this->queryBuilder
            ->from(QueueRepository::TABLE_NAME)
            ->select('*')->where(
                $this->queryBuilder->expr()->eq('qid', $this->queryBuilder->createNamedParameter($queueId))
            )->executeQuery()
            ->fetchAssociative();

        // Explode values
        $q_entry['parameters'] = $this->jsonCompatibilityConverter->convert($q_entry['parameters']);
        $q_entry['result_data'] = $this->jsonCompatibilityConverter->convert($q_entry['result_data']);
        $resStatus = ResultHandler::getResStatus($q_entry['result_data']);
        if (is_array($q_entry['result_data'])) {
            $q_entry['result_data']['content'] = $this->jsonCompatibilityConverter->convert(
                $q_entry['result_data']['content']
            );
            if (!$this->showResultLog) {
                if (is_array($q_entry['result_data']['content'])) {
                    unset($q_entry['result_data']['content']['log']);
                }
            }
        }
        return [$q_entry, $resStatus];
    }

    /**
     * @throws RouteNotFoundException
     */
    private function assignValues(): ModuleTemplate
    {
        // Look for set ID sent - if it is, we will display contents of that set:
        $this->showSetId = (int) GeneralUtility::_GP('setID');
        $this->CSVExport = (bool) GeneralUtility::_GP('_csv');
        $logEntriesPerPage = [];
        $csvData = [];

        if (GeneralUtility::_GP('qid_read')) {
            $this->crawlerController->readUrl((int) GeneralUtility::_GP('qid_read'), true);
        }

        if ($this->queueId) {
            // Get entry record:
            [$q_entry, $resStatus] = $this->getQueueEntry($this->queueId);
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
            if ($this->logDepth) {
                $tree->getTree($this->pageUid, (int) $this->logDepth);
            }

            // If Flush button is pressed, flush tables instead of selecting entries:
            if (GeneralUtility::_POST('_flush')) {
                $doFlush = true;
            } elseif (GeneralUtility::_POST('_flush_all')) {
                $doFlush = true;
                $this->logDisplay = 'all';
            } else {
                $doFlush = false;
            }

            $queueFilter = new QueueFilter($this->logDisplay);

            if ($doFlush) {
                $this->queueRepository->flushQueue($queueFilter);
            }

            // Traverse page tree:
            $count = 0;

            foreach ($tree->tree as $data) {
                $logEntriesOfPage = $this->queueRepository->getQueueEntriesForPageId(
                    (int) $data['row']['uid'],
                    $this->itemsPerPage,
                    $queueFilter
                );

                list($logEntriesPerPage[], $csvData) = $this->backendModuleLogService->addRows(
                    $logEntriesOfPage,
                    (int) GeneralUtility::_GP('setID'),
                    $data['HTML'] . BackendUtility::getRecordTitle('pages', $data['row'], true),
                    $this->showResultLog,
                    $this->showFeVars,
                    $this->CSVExport
                );
                if (++$count === 1000) {
                    break;
                }
            }

            $this->moduleTemplate->assign('logEntriesPerPage', $logEntriesPerPage);
        }

        if ($this->CSVExport) {
            $this->outputCsvFile($csvData);
        }

        return $this->moduleTemplate->assignMultiple([
            'actionUrl' => '',
            'queueId' => $this->queueId,
            'setId' => $this->showSetId,
            'noPageSelected' => false,
            'logEntriesPerPage' => $logEntriesPerPage,
            'showResultLog' => $this->showResultLog,
            'showFeVars' => $this->showFeVars,
            'displayActions' => 1,
            'displayLogFilterHtml' => $this->getDisplayLogFilterHtml(),
            'itemPerPageHtml' => $this->getItemsPerPageDropDownHtml(),
            'showResultLogHtml' => $this->getShowResultLogCheckBoxHtml(),
            'showFeVarsHtml' => $this->getShowFeVarsCheckBoxHtml(),
            'depthDropDownHtml' => $this->getDepthDropDownHtml(
                $this->pageUid,
                $this->logDepth,
                $this->backendModuleMenu['depth']
            ),
        ]);
    }

    private function getDisplayLogFilterHtml(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.display'
        ) . ': ' . BackendUtility::getFuncMenu(
            $this->pageUid,
            'logDisplay',
            $this->logDisplay,
            $this->backendModuleMenu['log_display'],
            'index.php',
            $this->getAdditionalQueryParams('logDisplay')
        );
    }

    private function getDepthDropDownHtml(int $id, string $currentValue, array $menuItems): string
    {
        return BackendUtility::getFuncMenu(
            $id,
            'logDepth',
            $currentValue,
            $menuItems,
            'index.php',
            $this->getAdditionalQueryParams('logDepth')
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
                $this->itemsPerPage,
                $this->backendModuleMenu['itemsPerPage'],
                'index.php',
                $this->getAdditionalQueryParams('itemsPerPage')
            );
    }

    private function getShowResultLogCheckBoxHtml(): string
    {
        return BackendUtility::getFuncCheck(
            $this->pageUid,
            'ShowResultLog',
            $this->showResultLog,
            'index.php',
            $this->quiPath . $this->getAdditionalQueryParams('ShowResultLog')
        ) . '&nbsp;' . $this->getLanguageService()->sL(
            'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showresultlog'
        );
    }

    private function getShowFeVarsCheckBoxHtml(): string
    {
        return BackendUtility::getFuncCheck(
            $this->pageUid,
            'ShowFeVars',
            $this->showFeVars,
            'index.php',
            $this->quiPath . $this->getAdditionalQueryParams('ShowFeVars')
        ) . '&nbsp;' . $this->getLanguageService()->sL(
            'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.showfevars'
        );
    }

    private function setPropertiesBasedOnPostVars(ServerRequestInterface $request): void
    {
        $this->pageUid = (int) ($request->getQueryParams()['id'] ?? -1);
        $this->setId = (int) GeneralUtility::_GP('setID');
        $this->quiPath = GeneralUtility::_GP('qid_details') ? '&qid_details=' . (int) GeneralUtility::_GP('qid_details') : '';
        $this->queueId = GeneralUtility::_GP('qid_details');
        $this->logDisplay = GeneralUtility::_GP('logDisplay') ?? 'all';
        $this->itemsPerPage = (int) (GeneralUtility::_GP('itemsPerPage') ?? 10);
        $this->showResultLog = (string) (GeneralUtility::_GP('ShowResultLog') ?? 0);
        $this->showFeVars = (string) (GeneralUtility::_GP('ShowFeVars') ?? 0);
        $this->logDepth = (string) (GeneralUtility::_GP('logDepth') ?? 0);
    }

    /*
     * Build query string with affected checkbox/dropdown value removed.
     */
    private function getAdditionalQueryParams(string $keyToBeRemoved): string
    {
        $queryString = '';
        $queryParams = [
            'setID' => $this->setId,
            'logDisplay' => $this->logDisplay,
            'itemsPerPage' => $this->itemsPerPage,
            'ShowFeVars' => $this->showFeVars,
            'ShowResultLog' => $this->showResultLog,
            'logDepth' => $this->logDepth,
        ];

        unset($queryParams[$keyToBeRemoved]);
        foreach ($queryParams as $key => $value) {
            $queryString .= "&{$key}={$value}";
        }
        return $queryString;
    }

    /**
     * Outputs the CSV file and sets the correct headers
     */
    private function outputCsvFile(array $csvData): void
    {
        if (!count($csvData)) {
            MessageUtility::addWarningMessage(
                $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:message.canNotExportEmptyQueueToCsvText'
                )
            );
            return;
        }

        $csvString = $this->csvWriter->arrayToCsv($csvData);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=CrawlerLog.csv');
        echo $csvString;

        exit;
    }
}
