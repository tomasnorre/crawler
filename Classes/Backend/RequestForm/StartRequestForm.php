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

use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Utility\MessageUtility;
use AOE\Crawler\Utility\SignalSlotUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

final class StartRequestForm extends AbstractRequestForm implements RequestFormInterface
{
    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var InfoModuleController
     */
    private $infoModuleController;

    /**
     * @var int
     */
    private $reqMinute = 1000;

    /**
     * @var array holds the selection of configuration from the configuration selector box
     */
    private $incomingConfigurationSelection = [];

    public function __construct(StandaloneView $view, InfoModuleController $infoModuleController, array $extensionSettings)
    {
        $this->view = $view;
        $this->infoModuleController = $infoModuleController;
        $this->extensionSettings = $extensionSettings;
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
        if (empty($pageId)) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noPageSelected'));
            return '';
        }

        $this->view->setTemplate('ShowCrawlerInformation');

        $crawlParameter = GeneralUtility::_GP('_crawl');
        $downloadParameter = GeneralUtility::_GP('_download');

        $submitCrawlUrls = isset($crawlParameter);
        $downloadCrawlUrls = isset($downloadParameter);
        $this->makeCrawlerProcessableChecks($this->extensionSettings);

        $scheduledTime = $this->getScheduledTime((string) GeneralUtility::_GP('tstamp'));

        $this->incomingConfigurationSelection = GeneralUtility::_GP('configurationSelection');
        $this->incomingConfigurationSelection = is_array($this->incomingConfigurationSelection) ? $this->incomingConfigurationSelection : [];

        $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
        $this->crawlerController->setID = GeneralUtility::md5int(microtime());

        $queueRows = '';
        $noConfigurationSelected = empty($this->incomingConfigurationSelection)
            || (count($this->incomingConfigurationSelection) === 1 && empty($this->incomingConfigurationSelection[0]));
        if ($noConfigurationSelected) {
            MessageUtility::addWarningMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noConfigSelected'));
        } else {
            if ($submitCrawlUrls) {
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

            $queueRows = $this->crawlerController->getPageTreeAndUrls(
                $pageId,
                $this->infoModuleController->MOD_SETTINGS['depth'],
                $scheduledTime,
                $this->reqMinute,
                $submitCrawlUrls,
                $downloadCrawlUrls,
                // Do not filter any processing instructions
                [],
                $this->incomingConfigurationSelection
            );
        }

        $downloadUrls = $this->crawlerController->downloadUrls;

        $duplicateTrack = $this->crawlerController->duplicateTrack;

        $this->view->assign('noConfigurationSelected', $noConfigurationSelected);
        $this->view->assign('submitCrawlUrls', $submitCrawlUrls);
        $this->view->assign('amountOfUrls', count($duplicateTrack));
        $this->view->assign('selectors', $this->generateConfigurationSelectors($pageId));
        $this->view->assign('queueRows', $queueRows);
        $this->view->assign('displayActions', 0);

        // Download Urls to crawl:
        if ($downloadCrawlUrls) {
            // Creating output header:
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=CrawlerUrls.txt');

            // Printing the content of the CSV lines:
            echo implode(chr(13) . chr(10), $downloadUrls);
            exit;
        }

        return $this->view->render();
    }

    /**
     * Generates the configuration selectors for compiling URLs:
     */
    private function generateConfigurationSelectors(int $pageId): array
    {
        $selectors = [];
        $selectors['depth'] = $this->selectorBox(
            [
                0 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                99 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
            'SET[depth]',
            $this->infoModuleController->MOD_SETTINGS['depth'],
            false
        );

        // Configurations
        $availableConfigurations = $this->crawlerController->getConfigurationsForBranch($pageId, (int) $this->infoModuleController->MOD_SETTINGS['depth'] ?: 0);
        $selectors['configurations'] = $this->selectorBox(
            empty($availableConfigurations) ? [] : array_combine($availableConfigurations, $availableConfigurations),
            'configurationSelection',
            $this->incomingConfigurationSelection,
            true
        );

        // Scheduled time:
        $selectors['scheduled'] = $this->selectorBox(
            [
                'now' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.time.now'),
                'midnight' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.time.midnight'),
                '04:00' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.time.4am'),
            ],
            'tstamp',
            GeneralUtility::_POST('tstamp'),
            false
        );

        return $selectors;
    }

    /**
     * Create selector box
     *
     * @param array $optArray Options key(value) => label pairs
     * @param string $name Selector box name
     * @param string|array $value Selector box value (array for multiple...)
     * @param boolean $multiple If set, will draw multiple box.
     *
     * @return string HTML select element
     */
    private function selectorBox($optArray, $name, $value, bool $multiple): string
    {
        if (! is_string($value) || ! is_array($value)) {
            $value = '';
        }

        $options = [];
        foreach ($optArray as $key => $val) {
            $selected = (! $multiple && ! strcmp($value, (string) $key)) || ($multiple && in_array($key, (array) $value, true));
            $options[] = '
                <option value="' . $key . '" ' . ($selected ? ' selected="selected"' : '') . '>' . htmlspecialchars($val, ENT_QUOTES | ENT_HTML5) . '</option>';
        }

        return '<select class="form-control" name="' . htmlspecialchars($name . ($multiple ? '[]' : ''), ENT_QUOTES | ENT_HTML5) . '"' . ($multiple ? ' multiple' : '') . '>' . implode('', $options) . '</select>';
    }

    /**
     * @return int
     */
    private function getScheduledTime(string $time)
    {
        switch ($time) {
            case 'midnight':
                $scheduledTime = mktime(0, 0, 0);
                break;
            case '04:00':
                $scheduledTime = mktime(0, 0, 0) + 4 * 3600;
                break;
            case 'now':
            default:
                $scheduledTime = time();
                break;
        }

        if (! $scheduledTime) {
            return time();
        }

        return $scheduledTime;
    }
}
