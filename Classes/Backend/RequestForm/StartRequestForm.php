<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend\RequestForm;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Utility\SignalSlotUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class StartRequestForm implements RequestForm
{
    /** @var StandaloneView */
    private $view;

    public function __construct(StandaloneView $view)
    {
        $this->view = $view;
    }

    public function render($id, string $elementName, array $menuItems): string
    {
        return $this->showCrawlerInformationAction($id);
    }

    /*******************************
     *
     * Generate URLs for crawling:
     *
     ******************************/

    /**
     * Show a list of URLs to be crawled for each page
     */
    private function showCrawlerInformationAction(int $pageId): string
    {
        $this->view->setTemplate('ShowCrawlerInformation');
        if (empty($pageId)) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noPageSelected'));
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
                MessageUtility::addWarningMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noConfigSelected'));
            } else {
                if ($this->submitCrawlUrls) {
                    $reason = new Reason();
                    $reason->setReason(Reason::REASON_GUI_SUBMIT);
                    $reason->setDetailText('The user ' . $GLOBALS['BE_USER']->user['username'] . ' added pages to the crawler queue manually');

                    $signalPayload = ['reason' => $reason];
                    SignalSlotUtility::emitSignal(
                        self::class,
                        SignalSlotUtility::SIGNAL_INVOKE_QUEUE_CHANGE,
                        $signalPayload
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
            $this->view->assign('displayActions', 0);

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

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
