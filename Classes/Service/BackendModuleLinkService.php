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
use AOE\Crawler\Controller\Backend\BackendModuleCrawlerProcessController;
use AOE\Crawler\Controller\Backend\Helper\UrlBuilder;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;

class BackendModuleLinkService
{
    public function __construct(
        private readonly IconFactory $iconFactory,
    ) {
    }

    /**
     * Returns a tag for the refresh icon
     *
     * @throws RouteNotFoundException
     */
    public function getRefreshLink(ModuleTemplate $moduleTemplate, int $pageUid): string
    {
        return $this->getLinkButton(
            $moduleTemplate,
            'actions-refresh',
            $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.refresh'),
            UrlBuilder::getBackendModuleUrl(
                [
                    'SET[\'crawleraction\']' => 'crawleraction',
                    'id' => $pageUid,
                ],
                BackendModuleCrawlerProcessController::BACKEND_MODULE
            )
        );
    }

    public function getFlushLink(ModuleTemplate $moduleTemplate): string
    {
        return $this->getLinkButton(
            $moduleTemplate,
            'actions-bolt',
            $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.process.flush'
            ),
            UrlBuilder::getBackendModuleUrl(
                [
                    'action' => 'flushProcess',
                ],
                BackendModuleCrawlerProcessController::BACKEND_MODULE
            )
        );
    }

    /**
     * @throws RouteNotFoundException
     */
    public function getAddLink(
        ModuleTemplate $moduleTemplate,
        int $currentActiveProcesses,
        int $maxActiveProcesses,
        bool $isCrawlerEnabled
    ): string {
        if (!$isCrawlerEnabled || $currentActiveProcesses >= $maxActiveProcesses) {
            return '';
        }

        return $this->getLinkButton(
            $moduleTemplate,
            'actions-add',
            $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.process.add'
            ),
            UrlBuilder::getBackendModuleUrl(
                [
                    'action' => 'addProcess',
                ],
                BackendModuleCrawlerProcessController::BACKEND_MODULE
            )
        );
    }

    /**
     * @throws RouteNotFoundException
     */
    public function getModeLink(ModuleTemplate $moduleTemplate, string $mode): string
    {
        if ($mode !== 'detail' && $mode !== 'simple') {
            return '';
        }

        if ($mode === 'detail') {
            $label = $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.show.running'
            );
            $linkMode = 'simple';
        } else {
            $label = $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.show.all'
            );
            $linkMode = 'detail';
        }

        return $this->getLinkButton(
            $moduleTemplate,
            'actions-document-view',
            $label,
            UrlBuilder::getBackendModuleUrl(
                [
                    'processListMode' => $linkMode,
                ],
                BackendModuleCrawlerProcessController::BACKEND_MODULE
            )
        );
    }

    /**
     * Returns a link for the panel to enable or disable the crawler
     *
     * @throws RouteNotFoundException
     */
    public function getEnableDisableLink(ModuleTemplate $moduleTemplate, bool $isCrawlerEnabled): string
    {
        if ($isCrawlerEnabled) {
            $iconIdentifier = 'tx-crawler-stop';
            $label = $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.disablecrawling'
            );
            $action = 'stopCrawling';
        } else {
            $iconIdentifier = 'tx-crawler-start';
            $label = $this->getLanguageService()->sL(
                'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.enablecrawling'
            );
            $action = 'resumeCrawling';
        }

        return $this->getLinkButton(
            $moduleTemplate,
            $iconIdentifier,
            $label,
            UrlBuilder::getBackendModuleUrl(
                [
                    'action' => $action,
                ],
                BackendModuleCrawlerProcessController::BACKEND_MODULE
            )
        );
    }

    private function getLinkButton(
        ModuleTemplate $moduleTemplate,
        string $iconIdentifier,
        string $title,
        UriInterface $href
    ): string {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        return (string) $buttonBar->makeLinkButton()
            ->setHref((string) $href)
            ->setIcon($this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL))
            ->setTitle($title)
            ->setShowLabelText(true);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
