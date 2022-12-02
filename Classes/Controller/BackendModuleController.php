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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class BackendModuleController
{
    private $pageUid;


    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected IconFactory $iconFactory,
        protected UriBuilder $uriBuilder
    )
    {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->setupView($request);
        $moduleTemplate->assign('currentPageId', $request->getQueryParams()['id']);
        $moduleTemplate->setTitle('Crawler', $GLOBALS['LANG']->getLL('module.menu.log'));

        return $moduleTemplate->renderResponse('Backend/ShowLog');
    }

    private function setupView(ServerRequestInterface $request): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->getView()->setLayoutRootPaths(['EXT:crawler/Resources/Private/Layouts']);
        $moduleTemplate->getView()->setPartialRootPaths(['EXT:crawler/Resources/Private/Partials']);
        $moduleTemplate->getView()->setTemplateRootPaths(['EXT:crawler/Resources/Private/Templates/Backend']);

        list($moduleTemplate, $context) = $this->setupMenu($request, $moduleTemplate);

        $moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:examples/Resources/Private/Language/Module/locallang_mod.xlf:mlang_tabs_tab'),
            $context
        );

        $permissionClause = $this->getBackendUserAuthentication()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess(
            $this->pageUid,
            $permissionClause
        );
        if ($pageRecord) {
            $moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageRecord);
        }
        //$moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());

        return $moduleTemplate;
    }

    public function setupMenu(ServerRequestInterface $request, ModuleTemplate $moduleTemplate): array
    {
        $menuItems = [
            'flash' => [
                'controller' => 'Module',
                'action' => 'flash',
                'label' => 'one',
            ],
            'tree' => [
                'controller' => 'Module',
                'action' => 'tree',
                'label' => 'two',
            ]
        ];
        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('ExampleModuleMenu');

        $context = '';
        foreach ($menuItems as $menuItemConfig) {
            $isActive = false; //$request->getControllerActionName() === $menuItemConfig['action'];
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItemConfig['label'])
                ->setHref($this->uriBuilder->reset()->uriFor(
                    $menuItemConfig['action'],
                    [],
                    $menuItemConfig['controller']
                ))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
            if ($isActive) {
                $context = $menuItemConfig['label'];
            }
        }

        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        return array($moduleTemplate, $context);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
