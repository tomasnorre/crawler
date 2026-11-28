<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

/*
 * (c) 2023-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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
use AOE\Crawler\Controller\Backend\BackendModuleCrawlerLogController;
use AOE\Crawler\Controller\Backend\Helper\UrlBuilder;
use AOE\Crawler\Value\CrawlResult;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendModuleLogService
{
    /**
     * The result status of a queue entry that is not crawled yet
     */
    public const STATUS_NOT_CRAWLED = '-';

    public function __construct(
        private readonly IconFactory $iconFactory
    ) {
    }

    /**
     * Create the rows for display of the page tree
     * For each page a number of rows are shown displaying GET variable configuration
     *
     * @throws RouteNotFoundException
     *
     * @return array{0: non-empty-list<array<string, mixed>>, 1: list<array<string, mixed>>}
     * @param array<array-key, array<string, mixed>> $logEntriesOfPage
     */
    public function addRows(
        array $logEntriesOfPage,
        int $setId,
        string $titleString,
        string $showResultLog,
        string $showFeVars,
        bool $CSVExport = false,
    ): array {
        $contentArray = $this->configureContentArray($showResultLog, $showFeVars);

        if ($logEntriesOfPage === []) {
            $contentArray['title'] = $titleString;
            $contentArray['noEntries'] = $this->getLanguageService()->sL('crawler.messages:labels.noentries');
            return [[$contentArray], []];
        }

        $refreshIcon = $this->iconFactory->getIcon('actions-system-refresh', IconSize::SMALL);
        $resultArray = [];
        $csvExport = [];
        foreach (array_values($logEntriesOfPage) as $index => $logEntry) {
            // Only the first row carries the page title, spanning all rows of the page
            $contentArray['title'] = $index === 0 ? $titleString : '';
            $contentArray['titleRowSpan'] = $index === 0 ? count($logEntriesOfPage) : 1;

            $contentArray = $this->buildRow(
                $logEntry,
                $contentArray,
                $setId,
                $showResultLog,
                $showFeVars,
                $refreshIcon
            );
            $resultArray[] = $contentArray;
            if ($CSVExport) {
                $csvExport[] = $this->extractCsvExport($logEntry, $contentArray['columns']);
            }
        }

        return [$resultArray, $csvExport];
    }

    /**
     * @return array{titleRowSpan: int, colSpan: int}
     */
    public function configureContentArray(string $showResultLog, string $showFeVars): array
    {
        return [
            'titleRowSpan' => 1,
            'colSpan' => 9
                        + ($showResultLog ? -1 : 0)
                        + ($showFeVars ? 3 : 0),
        ];
    }

    /**
     * @throws RouteNotFoundException
     * @param array<string, mixed> $logEntry
     * @param array<string, mixed> $contentArray
     * @return array<string, mixed>
     */
    private function buildRow(
        array $logEntry,
        array $contentArray,
        int $setId,
        string $showResultLog,
        string $showFeVars,
        Icon $refreshIcon,
    ): array {
        $result = CrawlResult::fromResultData((string) $logEntry['result_data']);
        $resStatus = $result?->getStatus() ?? self::STATUS_NOT_CRAWLED;
        $isError = str_contains($resStatus, 'Error');

        $rowData = $this->buildRowData($logEntry, $showResultLog, $resStatus, $result);
        $rowData = $this->populateFeVars($showFeVars, $result, $rowData);

        $contentArray['trClass'] = $isError ? 'bg-danger' : '';
        $contentArray['qid'] = [
            'link' => $this->getModuleUrl('qid_details', $logEntry['qid'], $setId),
            'link-text' => htmlspecialchars((string) $logEntry['qid'], ENT_QUOTES | ENT_HTML5),
        ];
        $contentArray['refresh'] = [
            'link' => $this->getModuleUrl('qid_read', $logEntry['qid'], $setId),
            'link-text' => $refreshIcon,
            'warning' => $isError ? $this->iconFactory->getIcon('actions-ban', IconSize::SMALL) : '',
        ];

        return $this->buildColumnsInContentArray($rowData, $contentArray);
    }

    /**
     * @param array<string, mixed> $logEntry
     * @return array<string, mixed>
     */
    private function buildRowData(
        array $logEntry,
        string $showResultLog,
        string $resStatus,
        ?CrawlResult $result
    ): array {
        $parameters = json_decode((string) $logEntry['parameters'], true);

        $rowData = [];
        if ($showResultLog) {
            $rowData['result_log'] = $this->getResultLog($result);
        } else {
            $rowData['scheduled'] = $this->formatDateTime((int) $logEntry['scheduled']);
            $rowData['exec_time'] = $this->formatDateTime((int) $logEntry['exec_time']);
        }
        $rowData['result_status'] = GeneralUtility::fixed_lgd_cs($resStatus, 50);
        $rowData['url'] = htmlspecialchars(
            (string) ($parameters['url'] ?? $parameters['alturl'] ?? ''),
            ENT_QUOTES | ENT_HTML5
        );
        $rowData['feUserGroupList'] = $parameters['feUserGroupList'] ?? '';
        $rowData['procInstructions'] = is_array($parameters['procInstructions'] ?? null)
            ? implode('; ', $parameters['procInstructions'])
            : '';
        $rowData['set_id'] = (string) $logEntry['set_id'];

        return $rowData;
    }

    private function formatDateTime(int $timestamp): string
    {
        return $timestamp > 0 ? BackendUtility::datetime($timestamp) : '-';
    }

    /**
     * @throws RouteNotFoundException
     */
    private function getModuleUrl(string $parameterName, mixed $queueId, int $setId): UriInterface
    {
        return UrlBuilder::getBackendModuleUrl(
            [
                $parameterName => $queueId,
                'setID' => $setId,
            ],
            BackendModuleCrawlerLogController::BACKEND_MODULE
        );
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @param array<string, mixed> $vv
     * @param array<string, mixed> $columns
     * @return array<string, mixed>
     */
    private function extractCsvExport(array $vv, array $columns): array
    {
        $resLog = $this->getResultLog(CrawlResult::fromResultData((string) ($vv['result_data'] ?? '')));
        $csvExport = [];
        // Only for CSV (adding qid and scheduled/exec_time if needed):
        $csvExport['scheduled'] = BackendUtility::datetime($vv['scheduled']);
        $csvExport['exec_time'] = $vv['exec_time'] ? BackendUtility::datetime($vv['exec_time']) : '-';
        $csvExport['result_status'] = $columns['result_status'];
        $csvExport['url'] = $columns['url'];
        $csvExport['feUserGroupList'] = $columns['feUserGroupList'];
        $csvExport['procInstructions'] = $columns['procInstructions'];
        $csvExport['set_id'] = $columns['set_id'];
        $csvExport['result_log'] = str_replace(chr(10), '// ', $resLog);
        $csvExport['qid'] = $vv['qid'];
        return $csvExport;
    }

    private function getResultLog(?CrawlResult $result): string
    {
        return implode(chr(10), $result?->getLog() ?? []);
    }

    /**
     * @param array<string, mixed> $rowData
     * @return array<string, mixed>
     */
    private function populateFeVars(string $showFeVars, ?CrawlResult $result, array $rowData): array
    {
        if ($showFeVars) {
            $resFeVars = $result?->getVars() ?? [];
            $rowData['tsfe_id'] = $resFeVars['id'] ?? '';
            $rowData['tsfe_gr_list'] = $resFeVars['gr_list'] ?? '';
        }
        return $rowData;
    }

    /**
     * @param array<string, mixed> $rowData
     * @param array<string, mixed> $contentArray
     * @return array<string, mixed>
     */
    private function buildColumnsInContentArray(array $rowData, array $contentArray): array
    {
        foreach ($rowData as $field => $value) {
            $contentArray['columns'][$field] = $field === 'url'
                ? $value
                : $this->escapeValue($value);
        }

        return $contentArray;
    }

    private function escapeValue(mixed $value): string
    {
        return nl2br(htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5));
    }
}
