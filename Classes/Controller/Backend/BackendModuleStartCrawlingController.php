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

use AOE\Crawler\Controller\Backend\Helper\UrlBuilder;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Event\InvokeQueueChangeEvent;
use AOE\Crawler\Utility\MessageUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v12.0.0
 */
class BackendModuleStartCrawlingController extends AbstractBackendModuleController implements BackendModuleControllerInterface
{
    private const BACKEND_MODULE = 'web_site_crawler_start';
    private const LINE_FEED = 10;
    private const CARRIAGE_RETURN = 13;
    private int $reqMinute = 1000;
    private EventDispatcher $eventDispatcher;

    /**
     * @var array holds the selection of configuration from the configuration selector box
     */
    private $incomingConfigurationSelection = [];

    public function __construct(
        private readonly CrawlerController $crawlerController,
        private readonly UriBuilder $backendUriBuilder,
    ) {
    }

    #[Required]
    public function setEventDispatcher(): void
    {
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->makeCrawlerProcessableChecks($this->extensionSettings);
        $this->pageUid = (int) ($request->getQueryParams()['id'] ?? -1);
        $this->moduleTemplate = $this->setupView($request, $this->pageUid);
        $this->moduleTemplate->makeDocHeaderModuleMenu(['id' => $request->getQueryParams()['id'] ?? -1]);

        $this->assignValues();
        return $this->moduleTemplate->renderResponse('Backend/ShowCrawlerInformation');
    }

    private function assignValues(): ModuleTemplate
    {
        $logUrl = $this->backendUriBuilder->buildUriFromRoute('web_site_crawler_log', ['id' => $this->pageUid]);

        $crawlingDepth = GeneralUtility::_GP('crawlingDepth') ?? '0';
        $crawlParameter = GeneralUtility::_GP('_crawl');
        $downloadParameter = GeneralUtility::_GP('_download');
        $scheduledTime = $this->getScheduledTime((string) GeneralUtility::_GP('tstamp'));
        $submitCrawlUrls = isset($crawlParameter);
        $downloadCrawlUrls = isset($downloadParameter);

        $this->incomingConfigurationSelection = GeneralUtility::_GP('configurationSelection');
        $this->incomingConfigurationSelection = is_array(
            $this->incomingConfigurationSelection
        ) ? $this->incomingConfigurationSelection : [];

        //$this->crawlerController = $this->getCrawlerController();
        $this->crawlerController->setID = GeneralUtility::md5int(microtime());

        $queueRows = '';
        $noConfigurationSelected = empty($this->incomingConfigurationSelection)
            || (count($this->incomingConfigurationSelection) === 1 && empty($this->incomingConfigurationSelection[0]));
        if ($noConfigurationSelected) {
            MessageUtility::addWarningMessage(
                $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.noConfigSelected'
                )
            );
        } else {
            if ($submitCrawlUrls) {
                $reason = new Reason();
                $reason->setReason(Reason::REASON_GUI_SUBMIT);
                $reason->setDetailText(
                    'The user ' . $GLOBALS['BE_USER']->user['username'] . ' added pages to the crawler queue manually'
                );
                $this->eventDispatcher->dispatch(new InvokeQueueChangeEvent($reason));
            }

            $queueRows = $this->crawlerController->getPageTreeAndUrls(
                $this->pageUid,
                $crawlingDepth,
                $scheduledTime,
                $this->reqMinute,
                $submitCrawlUrls,
                $downloadCrawlUrls,
                // Do not filter any processing instructions
                [],
                $this->incomingConfigurationSelection
            );
        }

        // Download Urls to crawl:
        $downloadUrls = $this->crawlerController->downloadUrls;
        if ($downloadCrawlUrls) {
            // Creating output header:
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=CrawlerUrls.txt');

            // Printing the content of the CSV lines:
            echo implode(chr(self::CARRIAGE_RETURN) . chr(self::LINE_FEED), $downloadUrls);
            exit;
        }

        return $this->moduleTemplate->assignMultiple([
            'currentPageId' => $this->pageUid,
            'noConfigurationSelected' => $noConfigurationSelected,
            'submitCrawlUrls' => $submitCrawlUrls,
            'amountOfUrls' => count($this->crawlerController->duplicateTrack ?? []),
            'selectors' => $this->generateConfigurationSelectors($this->pageUid, $crawlingDepth),
            'queueRows' => $queueRows,
            'displayActions' => 0,
            'actionUrl' => $this->getActionUrl(),
            'logUrl' => $logUrl,
        ]);
    }

    /**
     * Generates the configuration selectors for compiling URLs:
     */
    private function generateConfigurationSelectors(int $pageId, string $crawlingDepth): array
    {
        $selectors = [];
        $selectors['depth'] = $this->selectorBox(
            [
                0 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'
                ),
                1 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'
                ),
                2 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'
                ),
                3 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'
                ),
                4 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'
                ),
                99 => $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'
                ),
            ],
            'crawlingDepth',
            $crawlingDepth,
            false
        );

        // Configurations
        $availableConfigurations = $this->crawlerController->getConfigurationsForBranch(
            $pageId,
            (int) $crawlingDepth,
        );
        $selectors['configurations'] = $this->selectorBox(
            empty($availableConfigurations) ? [] : array_combine($availableConfigurations, $availableConfigurations),
            'configurationSelection',
            $this->incomingConfigurationSelection,
            true
        );

        // Scheduled time:
        $selectors['scheduled'] = $this->selectorBox(
            [
                'now' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.time.now'
                ),
                'midnight' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.time.midnight'
                ),
                '04:00' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.time.4am'
                ),
            ],
            'tstamp',
            GeneralUtility::_GP('tstamp'),
            false
        );

        return $selectors;
    }

    /**
     * Create selector box
     *
     * @param array $optArray Options key(value) => label pairs
     * @param string $name Selector box name
     * @param string|array|null $value Selector box value (array for multiple...)
     * @param boolean $multiple If set, will draw multiple box.
     *
     * @return string HTML select element
     */
    private function selectorBox($optArray, $name, string|array|null $value, bool $multiple): string
    {
        if (!is_string($value) || !is_array($value)) {
            $value = '';
        }

        $options = [];
        foreach ($optArray as $key => $val) {
            $selected = (!$multiple && !strcmp($value, (string) $key)) || ($multiple && in_array(
                $key,
                (array) $value,
                true
            ));
            $options[] = '
                <option value="' . $key . '" ' . ($selected ? ' selected="selected"' : '') . '>' . htmlspecialchars(
                (string) $val,
                ENT_QUOTES | ENT_HTML5
            ) . '</option>';
        }

        return '<select class="form-control" name="' . htmlspecialchars(
            $name . ($multiple ? '[]' : ''),
            ENT_QUOTES | ENT_HTML5
        ) . '"' . ($multiple ? ' multiple' : '') . '>' . implode('', $options) . '</select>';
    }

    private function getScheduledTime(string $time): float|int
    {
        $scheduledTime = match ($time) {
            'midnight' => mktime(0, 0, 0),
            '04:00' => mktime(0, 0, 0) + 4 * 3600,
            default => time(),
        };

        if (!$scheduledTime) {
            return time();
        }

        return $scheduledTime;
    }

    /**
     * @throws RouteNotFoundException
     */
    private function getActionUrl(): Uri
    {
        return GeneralUtility::makeInstance(UrlBuilder::class)->getBackendModuleUrl([], self::BACKEND_MODULE);
    }
}
