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
use AOE\Crawler\Utility\PhpBinaryUtility;
use AOE\Crawler\Utility\SignalSlotUtility;
use AOE\Crawler\Value\ModuleSettings;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

final class StartRequestForm extends AbstractRequestForm implements RequestForm
{
    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var ModuleSettings
     */
    private $moduleSettings;

    /**
     * @var InfoModuleController
     */
    private $infoModuleController;

    public function __construct(StandaloneView $view, ModuleSettings $moduleSettings, InfoModuleController $infoModuleController)
    {
        $this->view = $view;
        $this->moduleSettings = $moduleSettings;
        $this->infoModuleController = $infoModuleController;
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
                    $this->pageId,
                    4,
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

    /**
     * Verify that the crawler is executable.
     * TODO: popen() is part of PHP Core and check can be removed. The PHP Binary check should be moved to PhPBinaryUtility::class
     */
    private function makeCrawlerProcessableChecks(): void
    {
        if (!$this->isPhpForkAvailable()) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:message.noPhpForkAvailable'));
        }

        $exitCode = 0;
        $out = [];
        CommandUtility::exec(
            PhpBinaryUtility::getPhpBinary() . ' -v',
            $out,
            $exitCode
        );
        if ($exitCode > 0) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:message.phpBinaryNotFound'), htmlspecialchars($this->extensionSettings['phpPath'])));
        }
    }

    /**
     * Indicate that the required PHP method "popen" is
     * available in the system.
     */
    private function isPhpForkAvailable(): bool
    {
        return function_exists('popen');
    }

    /**
     * Generates the configuration selectors for compiling URLs:
     */
    private function generateConfigurationSelectors(): array
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
        $availableConfigurations = $this->crawlerController->getConfigurationsForBranch((int) $this->pageId, (int) $this->infoModuleController->MOD_SETTINGS['depth'] ?: 0);
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
        if (!is_string($value) || !is_array($value)) {
            $value = '';
        }

        $options = [];
        foreach ($optArray as $key => $val) {
            $selected = (!$multiple && !strcmp($value, (string) $key)) || ($multiple && in_array($key, (array) $value, true));
            $options[] = '
                <option value="' . $key . '" ' . ($selected ? ' selected="selected"' : '') . '>' . htmlspecialchars($val, ENT_QUOTES | ENT_HTML5) . '</option>';
        }

        return '<select class="form-control" name="' . htmlspecialchars($name . ($multiple ? '[]' : ''), ENT_QUOTES | ENT_HTML5) . '"' . ($multiple ? ' multiple' : '') . '>' . implode('', $options) . '</select>';
    }
}
