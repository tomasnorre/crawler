<?php

declare(strict_types=1);

namespace AOE\Crawler\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;

class BackendModuleController
{

    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected IconFactory $iconFactory
    )
    {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->setupView($request);
        $moduleTemplate->assign('currentPageId', $request->getQueryParams()['id']);

        $this->setUpDocHeader($request, $moduleTemplate);
        $moduleTemplate->setTitle('Crawler', $GLOBALS['LANG']->getLL('module.menu.log'));

        return $moduleTemplate->renderResponse('Backend/ShowLog');
    }

    private function setupView(ServerRequestInterface $request): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->getView()->setLayoutRootPaths(['EXT:crawler/Resources/Private/Layouts']);
        $moduleTemplate->getView()->setPartialRootPaths(['EXT:crawler/Resources/Private/Partials']);
        $moduleTemplate->getView()->setTemplateRootPaths(['EXT:crawler/Resources/Private/Templates/Backend']);
        return $moduleTemplate;
    }

    private function setUpDocHeader(
        ServerRequestInterface $request,
        ModuleTemplate         $view
    )
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $list = $buttonBar->makeLinkButton()
            ->setHref('<uri-builder-path>')
            ->setTitle('A Title')
            ->setShowLabelText('Link')
            ->setIcon($this->iconFactory->getIcon('actions-extension-import', Icon::SIZE_SMALL));
        $buttonBar->addButton($list, ButtonBar::BUTTON_POSITION_LEFT, 1);
    }
}
