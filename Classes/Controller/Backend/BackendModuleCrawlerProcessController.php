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
use AOE\Crawler\Crawler;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Exception\ProcessException;
use AOE\Crawler\Hooks\CrawlerHookInterface;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Utility\MessageUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal since v12.0.0
 */
final class BackendModuleCrawlerProcessController extends AbstractBackendModuleController implements BackendModuleControllerInterface
{
    public const BACKEND_MODULE = 'web_site_crawler_process';

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly ProcessService $processService,
        private readonly ProcessRepository $processRepository,
        private readonly QueueRepository $queueRepository,
        private readonly Crawler $crawler
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageUid = (int) ($request->getQueryParams()['id'] ?? -1);
        $this->moduleTemplate = $this->setupView($request, $this->pageUid);
        $this->moduleTemplate->assign('currentPageId', $this->pageUid);
        $this->moduleTemplate->setTitle('Crawler', $GLOBALS['LANG']->getLL('module.menu.log'));
        $this->moduleTemplate = $this->moduleTemplate->makeDocHeaderModuleMenu(
            ['id' => $request->getQueryParams()['id'] ?? -1]
        );
        $this->moduleTemplate = $this->assignValues();
        $this->runRefreshHooks();

        return $this->moduleTemplate->renderResponse('Backend/ProcessOverview');
    }

    private function assignValues(): ModuleTemplate
    {
        try {
            $this->handleProcessOverviewActions();
        } catch (\Throwable $e) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($e->getMessage());
        }

        $mode = GeneralUtility::_GP('processListMode') ?? 'simple';
        $allProcesses = $mode === 'simple' ? $this->processRepository->findAllActive() : $this->processRepository->findAll();
        $isCrawlerEnabled = ! $this->crawler->isDisabled() && ! $this->isErrorDetected;
        $currentActiveProcesses = $this->processRepository->findAllActive()->count();
        $maxActiveProcesses = MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1);

        return $this->moduleTemplate->assignMultiple([
            'pageId' => $this->pageUid,
            'refreshLink' => $this->getRefreshLink(),
            'addLink' => $this->getAddLink($currentActiveProcesses, $maxActiveProcesses, $isCrawlerEnabled),
            'modeLink' => $this->getModeLink($mode),
            'enableDisableToggle' => $this->getEnableDisableLink($isCrawlerEnabled),
            'processCollection' => $allProcesses,
            'cliPath' => $this->processService->getCrawlerCliPath(),
            'isCrawlerEnabled' => $isCrawlerEnabled,
            'totalUnprocessedItemCount' => $this->queueRepository->countAllPendingItems(),
            'assignedUnprocessedItemCount' => $this->queueRepository->countAllAssignedPendingItems(),
            'activeProcessCount' => $currentActiveProcesses,
            'maxActiveProcessCount' => $maxActiveProcesses,
            'mode' => $mode,
            'displayActions' => 0,
        ]);
    }

    /**
     * Method to handle incoming actions of the process overview
     *
     * @throws ProcessException
     */
    private function handleProcessOverviewActions(): void
    {
        switch (GeneralUtility::_GP('action')) {
            case 'stopCrawling':
                //set the cli status to disable (all processes will be terminated)
                $this->crawler->setDisabled(true);
                break;
            case 'addProcess':
                if ($this->processService->startProcess() === false) {
                    throw new ProcessException($this->getLanguageService()->sL(
                        'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.newprocesserror'
                    ));
                }
                MessageUtility::addNoticeMessage(
                    $this->getLanguageService()->sL(
                        'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.newprocess'
                    )
                );
                break;
            case 'resumeCrawling':
            default:
                //set the cli status to end (all processes will be terminated)
                $this->crawler->setDisabled(false);
                break;
        }
    }

    /**
     * Returns a tag for the refresh icon
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    private function getRefreshLink(): string
    {
        return $this->getLinkButton(
            'actions-refresh',
            $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.refresh'),
            UrlBuilder::getBackendModuleUrl(
                ['SET[\'crawleraction\']' => 'crawleraction', 'id' => $this->pageUid],
                self::BACKEND_MODULE
            )
        );
    }

    /**
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    private function getAddLink(int $currentActiveProcesses, int $maxActiveProcesses, bool $isCrawlerEnabled): string
    {
        if (! $isCrawlerEnabled) {
            return '';
        }
        if ($currentActiveProcesses >= $maxActiveProcesses) {
            return '';
        }

        return $this->getLinkButton(
            'actions-add',
            $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.process.add'
            ),
            UrlBuilder::getBackendModuleUrl(['action' => 'addProcess'], self::BACKEND_MODULE)
        );
    }

    /**
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    private function getModeLink(string $mode): string
    {
        if ($mode === 'detail') {
            return $this->getLinkButton(
                'actions-document-view',
                $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.show.running'
                ),
                UrlBuilder::getBackendModuleUrl(['processListMode' => 'simple'], self::BACKEND_MODULE)
            );
        } elseif ($mode === 'simple') {
            return $this->getLinkButton(
                'actions-document-view',
                $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.show.all'
                ),
                UrlBuilder::getBackendModuleUrl(['processListMode' => 'detail'], self::BACKEND_MODULE)
            );
        }
        return '';
    }

    /**
     * Returns a link for the panel to enable or disable the crawler
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    private function getEnableDisableLink(bool $isCrawlerEnabled): string
    {
        if ($isCrawlerEnabled) {
            return $this->getLinkButton(
                'tx-crawler-stop',
                $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.disablecrawling'
                ),
                UrlBuilder::getBackendModuleUrl(['action' => 'stopCrawling'], self::BACKEND_MODULE)
            );
        }
        return $this->getLinkButton(
            'tx-crawler-start',
            $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.enablecrawling'
            ),
            UrlBuilder::getBackendModuleUrl(['action' => 'resumeCrawling'], self::BACKEND_MODULE)
        );
    }

    /**
     * Activate hooks
     */
    private function runRefreshHooks(): void
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['refresh_hooks'] ?? [] as $objRef) {
            /** @var CrawlerHookInterface $hookObj */
            $hookObj = GeneralUtility::makeInstance($objRef);
            if (is_object($hookObj)) {
                $hookObj->crawler_init();
            }
        }
    }

    private function getLinkButton(string $iconIdentifier, string $title, UriInterface $href): string
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        return (string) $buttonBar->makeLinkButton()
            ->setHref((string) $href)
            ->setIcon($this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL))
            ->setTitle($title)
            ->setShowLabelText(true);
    }
}
