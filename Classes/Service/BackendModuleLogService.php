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
use AOE\Crawler\Controller\Backend\Helper\ResultHandler;
use AOE\Crawler\Controller\Backend\Helper\UrlBuilder;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendModuleLogService
{
    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly JsonCompatibilityConverter $jsonCompatibilityConverter,
    ) {
    }

    /**
     * Create the rows for display of the page tree
     * For each page a number of rows are shown displaying GET variable configuration
     *
     * @throws RouteNotFoundException
     *
     * @psalm-return non-empty-list<array{titleRowSpan: positive-int, colSpan: int, title: string, noEntries?: string, trClass?: string, qid?: array{link: Uri, link-text: string}, refresh?: array{link: Uri, link-text: Icon, warning: (Icon | string)}, columns?: array{url: (mixed | string), scheduled: string, exec_time: string, result_log: string, result_status: string, feUserGroupList: string, procInstructions: string, set_id: string, tsfe_id: string, tsfe_gr_list: string}}>
     */
    public function addRows(
        array $logEntriesOfPage,
        int $setId,
        string $titleString,
        string $showResultLog,
        string $showFeVars,
        bool $CSVExport
    ): array {
        $resultArray = [];
        $contentArray = [];
        $csvExport = [];

        $contentArray['titleRowSpan'] = 1;
        $contentArray['colSpan'] = 9
            + ($showResultLog ? -1 : 0)
            + ($showFeVars ? 3 : 0);

        if (!empty($logEntriesOfPage)) {
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
                if ($showResultLog) {
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

                if ($showFeVars) {
                    $resFeVars = ResultHandler::getResFeVars($resultData ?: []);
                    $rowData['tsfe_id'] = $resFeVars['id'] ?? '';
                    $rowData['tsfe_gr_list'] = $resFeVars['gr_list'] ?? '';
                }

                $trClass = '';
                $warningIcon = '';
                if (str_contains($resStatus, 'Error')) {
                    $trClass = 'bg-danger';
                    $warningIcon = $this->iconFactory->getIcon('actions-ban', Icon::SIZE_SMALL);
                }

                // Put rows together:
                $contentArray['trClass'] = $trClass;
                $contentArray['qid'] = [
                    'link' => UrlBuilder::getBackendModuleUrl(
                        [
                            'qid_details' => $vv['qid'],
                            'setID' => $setId,
                        ],
                        BackendModuleCrawlerLogController::BACKEND_MODULE
                    ),
                    'link-text' => htmlspecialchars((string) $vv['qid'], ENT_QUOTES | ENT_HTML5),
                ];
                $contentArray['refresh'] = [
                    'link' => UrlBuilder::getBackendModuleUrl(
                        [
                            'qid_read' => $vv['qid'],
                            'setID' => $setId,
                        ],
                        BackendModuleCrawlerLogController::BACKEND_MODULE
                    ),
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

                if ($CSVExport) {
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
                }
            }
        } else {
            $contentArray['title'] = $titleString;
            $contentArray['noEntries'] = $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noentries'
            );

            $resultArray[] = $contentArray;
        }

        return [$resultArray, $csvExport];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
