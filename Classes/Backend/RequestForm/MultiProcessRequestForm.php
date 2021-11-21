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

use AOE\Crawler\Backend\Helper\UrlBuilder;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Crawler;
use AOE\Crawler\Domain\Repository\ProcessRepository;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Exception\ProcessException;
use AOE\Crawler\Hooks\CrawlerHookInterface;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Utility\MessageUtility;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

final class MultiProcessRequestForm extends AbstractRequestForm implements RequestFormInterface
{
    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var ProcessService
     */
    private $processService;

    /**
     * @var IconFactory
     */
    private $iconFactory;

    /**
     * @var InfoModuleController
     */
    private $infoModuleController;

    /**
     * @var int|mixed
     */
    private $id;

    /**
     * @var Crawler
     */
    private $crawler;

    public function __construct(StandaloneView $view, InfoModuleController $infoModuleController, array $extensionSettings)
    {
        $this->view = $view;
        $this->processService = GeneralUtility::makeInstance(ProcessService::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->infoModuleController = $infoModuleController;
        $this->extensionSettings = $extensionSettings;
        $this->crawler = GeneralUtility::makeInstance(Crawler::class);
    }

    public function render($id, string $elementName, array $menuItems): string
    {
        return $this->processOverviewAction();
    }

    /**
     * This method is used to show an overview about the active an the finished crawling processes
     *
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    private function processOverviewAction()
    {
        $this->view->setTemplate('ProcessOverview');
        $this->runRefreshHooks();
        $this->makeCrawlerProcessableChecks($this->extensionSettings);

        try {
            $this->handleProcessOverviewActions();
        } catch (\Throwable $e) {
            $this->isErrorDetected = true;
            MessageUtility::addErrorMessage($e->getMessage());
        }

        $processRepository = GeneralUtility::makeInstance(ProcessRepository::class);
        $queueRepository = GeneralUtility::makeInstance(QueueRepository::class);

        $mode = GeneralUtility::_GP('processListMode') ?? $this->infoModuleController->MOD_SETTINGS['processListMode'];
        if ($mode === 'simple') {
            $allProcesses = $processRepository->findAllActive();
        } else {
            $allProcesses = $processRepository->findAll();
        }
        $isCrawlerEnabled = ! $this->crawler->isDisabled() && ! $this->isErrorDetected;
        $currentActiveProcesses = $processRepository->findAllActive()->count();
        $maxActiveProcesses = MathUtility::forceIntegerInRange($this->extensionSettings['processLimit'], 1, 99, 1);
        $this->view->assignMultiple([
            'pageId' => (int) $this->id,
            'refreshLink' => $this->getRefreshLink(),
            'addLink' => $this->getAddLink($currentActiveProcesses, $maxActiveProcesses, $isCrawlerEnabled),
            'modeLink' => $this->getModeLink($mode),
            'enableDisableToggle' => $this->getEnableDisableLink($isCrawlerEnabled),
            'processCollection' => $allProcesses,
            'cliPath' => $this->processService->getCrawlerCliPath(),
            'isCrawlerEnabled' => $isCrawlerEnabled,
            'totalUnprocessedItemCount' => $queueRepository->countAllPendingItems(),
            'assignedUnprocessedItemCount' => $queueRepository->countAllAssignedPendingItems(),
            'activeProcessCount' => $currentActiveProcesses,
            'maxActiveProcessCount' => $maxActiveProcesses,
            'mode' => $mode,
            'displayActions' => 0,
        ]);

        return $this->view->render();
    }

    private function getLinkButton(string $iconIdentifier, string $title, UriInterface $href): string
    {
        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        return (string) $buttonBar->makeLinkButton()
            ->setHref((string) $href)
            ->setIcon($this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL))
            ->setTitle($title)
            ->setShowLabelText(true);
    }

    /**
     * Method to handle incomming actions of the process overview
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
                    throw new ProcessException($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.newprocesserror'));
                }
                MessageUtility::addNoticeMessage($this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.newprocess'));
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
            UrlBuilder::getInfoModuleUrl(['SET[\'crawleraction\']' => 'crawleraction', 'id' => $this->id])
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
            $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.process.add'),
            UrlBuilder::getInfoModuleUrl(['action' => 'addProcess'])
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
                $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.show.running'),
                UrlBuilder::getInfoModuleUrl(['processListMode' => 'simple'])
            );
        } elseif ($mode === 'simple') {
            return $this->getLinkButton(
                'actions-document-view',
                $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.show.all'),
                UrlBuilder::getInfoModuleUrl(['processListMode' => 'detail'])
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
                $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.disablecrawling'),
                UrlBuilder::getInfoModuleUrl(['action' => 'stopCrawling'])
            );
        }
        return $this->getLinkButton(
            'tx-crawler-start',
            $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.enablecrawling'),
            UrlBuilder::getInfoModuleUrl(['action' => 'resumeCrawling'])
        );
    }

    /**
     * Activate hooks
     */
    private function runRefreshHooks(): void
    {
        $crawlerLib = GeneralUtility::makeInstance(CrawlerController::class);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['refresh_hooks'] ?? [] as $objRef) {
            /** @var CrawlerHookInterface $hookObj */
            $hookObj = GeneralUtility::makeInstance($objRef);
            if (is_object($hookObj)) {
                $hookObj->crawler_init($crawlerLib);
            }
        }
    }
}
