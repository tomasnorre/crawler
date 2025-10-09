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
use AOE\Crawler\Crawler;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Exception\ProcessException;
use AOE\Crawler\Hooks\CrawlerHookInterface;
use AOE\Crawler\Service\BackendModuleLinkService;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Utility\MessageUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal since v12.0.0
 */
final class BackendModuleCrawlerProcessController extends AbstractBackendModuleController implements BackendModuleControllerInterface
{
    public const BACKEND_MODULE = 'web_site_crawler_process';

    public function __construct(
        private readonly ProcessService $processService,
        private readonly ProcessRepository $processRepository,
        private readonly QueueRepository $queueRepository,
        private readonly BackendModuleLinkService $backendModuleLinkService,
        private readonly Crawler $crawler
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageUid = (int) ($request->getQueryParams()['id'] ?? -1);
        $this->moduleTemplate = $this->setupView($request, $this->pageUid);
        $this->moduleTemplate->assign('currentPageId', $this->pageUid);
        $this->moduleTemplate = $this->moduleTemplate->makeDocHeaderModuleMenu(
            [
                'id' => $request->getQueryParams()['id'] ?? -1,
            ]
        );
        $this->moduleTemplate = $this->assignValues($request);
        $this->runRefreshHooks();

        return $this->moduleTemplate->renderResponse('Backend/ProcessOverview');
    }

    private function assignValues(ServerRequestInterface $request): ModuleTemplate
    {
        try {
            $this->handleProcessOverviewActions($request);
        } catch (\Throwable $e) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($e->getMessage());
        }

        $mode = RequestHelper::getStringFromRequest($request, 'processListMode', 'simple');
        $allProcesses = $mode === 'simple' ? $this->processRepository->findAllActive() : $this->processRepository->findAll();
        $isCrawlerEnabled = !$this->crawler->isDisabled() && !$this->isErrorDetected;
        $currentActiveProcesses = $this->processRepository->findAllActive()->count();
        $maxActiveProcesses = MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1);

        return $this->moduleTemplate->assignMultiple([
            'pageId' => $this->pageUid,
            'refreshLink' => $this->backendModuleLinkService->getRefreshLink($this->moduleTemplate, $this->pageUid),
            'addLink' => $this->backendModuleLinkService->getAddLink(
                $this->moduleTemplate,
                $currentActiveProcesses,
                $maxActiveProcesses,
                $isCrawlerEnabled
            ),
            'modeLink' => $this->backendModuleLinkService->getModeLink($this->moduleTemplate, $mode),
            'flushLink' => $this->backendModuleLinkService->getFlushLink($this->moduleTemplate),
            'enableDisableToggle' => $this->backendModuleLinkService->getEnableDisableLink(
                $this->moduleTemplate,
                $isCrawlerEnabled
            ),
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
    private function handleProcessOverviewActions(ServerRequestInterface $request): void
    {
        $action = RequestHelper::getStringFromRequest($request, 'action');

        switch ($action) {
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
            case 'flushProcess':
                $processes = $this->processRepository->findAll();
                foreach ($processes as $process) {
                    $this->processRepository->removeByProcessId($process->getProcessId());
                }
                break;
            case 'resumeCrawling':
            default:
                //set the cli status to end (all processes will be terminated)
                $this->crawler->setDisabled(false);
                break;
        }
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
}
