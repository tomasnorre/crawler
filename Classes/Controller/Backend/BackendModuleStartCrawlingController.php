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

use AOE\Crawler\Controller\Backend\Helper\RequestHelper;
use AOE\Crawler\Controller\Backend\Helper\UrlBuilder;
use AOE\Crawler\Controller\Backend\Interface\BackendModuleControllerInterface;
use AOE\Crawler\Domain\Model\Reason;
use AOE\Crawler\Event\InvokeQueueChangeEvent;
use AOE\Crawler\Queue\CrawlRun;
use AOE\Crawler\Service\PageConfigurationResolver;
use AOE\Crawler\Service\PageTreeUrlCollector;
use AOE\Crawler\Utility\MessageUtility;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Contracts\Service\Attribute\Required;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v12.0.0
 *
 * @psalm-api Backend module, registered in Configuration/Backend/Modules.php
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
    private array $incomingConfigurationSelection = [];

    public function __construct(
        private readonly PageTreeUrlCollector $pageTreeUrlCollector,
        private readonly PageConfigurationResolver $pageConfigurationResolver,
        private readonly UriBuilder $backendUriBuilder,
        private readonly ClockInterface $clock,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    #[Required]
    public function setEventDispatcher(): void
    {
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
    }

    #[\Override]
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageUid = (int) ($request->getQueryParams()['id'] ?? -1);
        $this->moduleTemplate = $this->setupView($request, $this->pageUid);
        $this->moduleTemplate->makeDocHeaderModuleMenu([
            'id' => $request->getQueryParams()['id'] ?? -1,
        ]);

        return $this->assignValues($request)
            ?? $this->moduleTemplate->renderResponse('Backend/ShowCrawlerInformation');
    }

    /**
     * Assigns the values to the view, or returns the download response when the URLs were requested as a download.
     */
    private function assignValues(ServerRequestInterface $request): ?ResponseInterface
    {
        $logUrl = $this->backendUriBuilder->buildUriFromRoute('web_site_crawler_log', [
            'id' => $this->pageUid,
        ]);

        $crawlingDepth = RequestHelper::getIntFromRequest($request, 'crawlingDepth');
        $submitCrawlUrls = RequestHelper::getBoolFromRequest($request, '_crawl');
        $downloadCrawlUrls = RequestHelper::getBoolFromRequest($request, '_download');
        $scheduledTime = $this->getScheduledTime(RequestHelper::getStringFromRequest($request, 'tstamp', 'now'));

        $this->incomingConfigurationSelection = RequestHelper::getArrayFromRequest($request, 'configurationSelection');

        $run = new CrawlRun(
            $scheduledTime,
            $this->reqMinute,
            submitUrls: $submitCrawlUrls,
            collectDownloadUrls: $downloadCrawlUrls,
            configurationSelection: $this->incomingConfigurationSelection,
        );

        $queueRows = '';
        $noConfigurationSelected = $this->isNoConfigurationSelected();
        if ($noConfigurationSelected) {
            MessageUtility::addWarningMessage(
                $this->getLanguageService()->sL('crawler.messages:labels.noConfigSelected')
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

            $queueRows = $this->pageTreeUrlCollector->collect($this->pageUid, $crawlingDepth, $run);
        }

        if ($downloadCrawlUrls) {
            return $this->createDownloadResponse($run->getDownloadUrls());
        }

        $this->moduleTemplate->assignMultiple([
            'currentPageId' => $this->pageUid,
            'noConfigurationSelected' => $noConfigurationSelected,
            'submitCrawlUrls' => $submitCrawlUrls,
            'amountOfUrls' => $run->countHandledUrls(),
            'selectors' => $this->generateConfigurationSelectors($this->pageUid, (string) $crawlingDepth, $request),
            'queueRows' => $queueRows,
            'displayActions' => 0,
            'actionUrl' => $this->getActionUrl(),
            'logUrl' => $logUrl,
        ]);

        return null;
    }

    /**
     * The URLs to crawl as a text file download, one URL per line.
     *
     * @param array<string> $downloadUrls
     */
    private function createDownloadResponse(array $downloadUrls): ResponseInterface
    {
        $body = implode(chr(self::CARRIAGE_RETURN) . chr(self::LINE_FEED), $downloadUrls);

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename=CrawlerUrls.txt')
            ->withBody($this->streamFactory->createStream($body));
    }

    /**
     * Generates the configuration selectors for compiling URLs:
     */
    private function generateConfigurationSelectors(
        int $pageId,
        string $crawlingDepth,
        ServerRequestInterface $request
    ): array {
        $selectors = [];
        $selectors['depth'] = $this->selectorBox(
            [
                0 => $this->getLanguageService()->sL('core.core:labels.depth_0'),
                1 => $this->getLanguageService()->sL('core.core:labels.depth_1'),
                2 => $this->getLanguageService()->sL('core.core:labels.depth_2'),
                3 => $this->getLanguageService()->sL('core.core:labels.depth_3'),
                4 => $this->getLanguageService()->sL('core.core:labels.depth_4'),
                99 => $this->getLanguageService()->sL('core.core:labels.depth_infi'),
            ],
            'crawlingDepth',
            $crawlingDepth,
            false
        );

        // Configurations
        $availableConfigurations = $this->pageConfigurationResolver->getConfigurationNamesForBranch(
            $pageId,
            (int) $crawlingDepth,
        );
        $selectors['configurations'] = $this->selectorBox(
            $availableConfigurations === [] ? [] : array_combine($availableConfigurations, $availableConfigurations),
            'configurationSelection',
            $this->incomingConfigurationSelection,
            true
        );

        // Scheduled time:
        $selectors['scheduled'] = $this->selectorBox(
            [
                'now' => $this->getLanguageService()->sL('crawler.messages:labels.time.now'),
                'midnight' => $this->getLanguageService()->sL('crawler.messages:labels.time.midnight'),
                '04:00' => $this->getLanguageService()->sL('crawler.messages:labels.time.4am'),
            ],
            'tstamp',
            RequestHelper::getStringFromRequest($request, 'tstamp', 'now'),
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
     * @param bool $multiple If set, will draw multiple box.
     *
     * @return string HTML select element
     */
    private function selectorBox(array $optArray, string $name, string|array|null $value, bool $multiple): string
    {
        if (!is_string($value) && !is_array($value)) {
            $value = '';
        }

        $options = [];
        foreach ($optArray as $key => $val) {
            $selected = $this->isOptionSelected($key, $value, $multiple);
            $options[] = '
                <option value="' . $key . '" ' . ($selected ? ' selected="selected"' : '') . '>' . htmlspecialchars(
                (string) $val,
                ENT_QUOTES | ENT_HTML5
            ) . '</option>';
        }

        return '<select class="form-select" name="' . htmlspecialchars(
            $name . ($multiple ? '[]' : ''),
            ENT_QUOTES | ENT_HTML5
        ) . '"' . ($multiple ? ' multiple' : '') . '>' . implode('', $options) . '</select>';
    }

    private function isOptionSelected(int|string $key, string|array $value, bool $multiple): bool
    {
        if ($multiple) {
            return in_array($key, (array) $value, true);
        }
        return is_string($value) && !strcmp($value, (string) $key);
    }

    private function getScheduledTime(string $time): int
    {
        $now = $this->clock->now();
        $midnight = $now->setTime(0, 0)->getTimestamp();

        return match ($time) {
            'midnight' => $midnight,
            '04:00' => $midnight + 4 * 3600,
            default => $now->getTimestamp(),
        };
    }

    /**
     * @throws RouteNotFoundException
     */
    private function getActionUrl(): UriInterface
    {
        return GeneralUtility::makeInstance(UrlBuilder::class)->getBackendModuleUrl([], self::BACKEND_MODULE);
    }

    private function isNoConfigurationSelected(): bool
    {
        return $this->incomingConfigurationSelection === []
            || (count($this->incomingConfigurationSelection) === 1 && empty($this->incomingConfigurationSelection[0]));
    }
}
