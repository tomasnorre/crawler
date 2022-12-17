<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend;

/*
 * (c) 2005-2021 AOE GmbH <dev@aoe.com>
 * (c) 2021-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Backend\RequestForm\RequestFormFactory;
use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Value\CrawlAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

/**
 * Function for Info module, containing three main actions:
 * - List of all queued items
 * - Log functionality
 * - Process overview
 *
 * @internal since v9.2.5
 */
class BackendModule
{
    protected int $id;
    protected ?string $crawlAction;
    protected array $extensionSettings = [];

    public function __construct(
        protected ProcessService $processManager,
        protected QueryBuilder $queryBuilder,
        protected QueueRepository $queueRepository,
        protected StandaloneView $view,
        protected InfoModuleController $pObj,
        protected BackendModuleSettings $backendModuleSettings,
        protected IconFactory $iconFactory,
        protected UriBuilder $uriBuilder,
        protected array $backendModuleMenu = []
    ) {
        $this->initializeView();
        $this->extensionSettings = GeneralUtility::makeInstance(
            ExtensionConfigurationProvider::class
        )->getExtensionConfiguration();
        $this->backendModuleMenu = $this->getModuleMenu();
    }

    /**
     * Called by the InfoModuleController
     */
    public function init(InfoModuleController $pObj): void
    {
        $this->pObj = $pObj;
        $this->id = (int) GeneralUtility::_GP('id');
        // Setting MOD_MENU items as we need them for logging:
        //$this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->getModuleMenu());
    }

    public function main(ServerRequestInterface $request): ResponseInterface
    {
        $this->id = (int) GeneralUtility::_GP('id');
        $this->crawlAction = $request->getQueryParams()['SET']['crawlaction'] ?? null;

        if (empty($this->backendModuleSettings->getProcessListMode())) {
            $this->backendModuleSettings->setProcessListMode('simple');
        }
        $this->view->assign('currentPageId', $this->id);

        $selectedAction = new CrawlAction($this->crawlAction ?? 'start');

        // Type function menu:
        $actionDropdown = BackendUtility::getFuncMenu(
            $this->id,
            'SET[crawlaction]',
            $selectedAction->__toString(),
            $this->backendModuleMenu['crawlaction']
        );

        $theOutput = '<h2>' . htmlspecialchars(
            $this->getLanguageService()->getLL('title'),
            ENT_QUOTES | ENT_HTML5
        ) . '</h2>' . $actionDropdown;

        //$theOutput . $this->renderForm($selectedAction);

        $moduleTemplate = (GeneralUtility::makeInstance(ModuleTemplateFactory::class))->create($request);
        $moduleTemplate->setContent($theOutput . $this->renderForm($selectedAction));

        $this->setUpDocHeader($request, $moduleTemplate);

        $factory = GeneralUtility::makeInstance(ResponseFactory::class);
        $response = $factory->createResponse();
        $response->getBody()->write($moduleTemplate->renderContent());

        return $response;
    }

    private function setUpDocHeader(ServerRequestInterface $request, ModuleTemplate $view)
    {
        $menuItems = [
            'flash' => [
                'controller' => 'Module',
                'action' => 'flash',
                'label' => $this->getLanguageService()->sL(
                    'LLL:EXT:examples/Resources/Private/Language/Module/locallang.xlf:module.menu.flash'
                ),
            ],
            ];

        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('ExampleModuleMenu');

        $context = '';
        foreach ($menuItems as $menuItemConfig) {
            $isActive = $request->getControllerActionName() === $menuItemConfig['action'];
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

        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);

        /*$list = $buttonBar->makeLinkButton()
            ->setHref('<uri-builder-path>')
            ->setTitle('A Title')
            ->setShowLabelText('Link')
            ->setIcon($this->iconFactory->getIcon('actions-extension-import', Icon::SIZE_SMALL));
        $buttonBar->addButton($list, ButtonBar::BUTTON_POSITION_LEFT, 1);
        */
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /*****************************
     *
     * General Helper Functions
     *
     *****************************/

    private function initializeView(): void
    {
        $this->view->setLayoutRootPaths(['EXT:crawler/Resources/Private/Layouts']);
        $this->view->setPartialRootPaths(['EXT:crawler/Resources/Private/Partials']);
        $this->view->setTemplateRootPaths(['EXT:crawler/Resources/Private/Templates/Backend']);
    }

    private function getModuleMenu(): array
    {
        return [
            'depth' => [
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
            'crawlaction' => [
                'start' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.start'
                ),
                'log' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.log'
                ),
                'multiprocess' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.multiprocess'
                ),
            ],
            'log_resultLog' => '',
            'log_feVars' => '',
            'processListMode' => '',
            'log_display' => [
                'all' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.all'
                ),
                'pending' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.pending'
                ),
                'finished' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.finished'
                ),
            ],
            'itemsPerPage' => [
                '5' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.5'
                ),
                '10' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.10'
                ),
                '50' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.50'
                ),
                '0' => $this->getLanguageService()->sL(
                    'LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.0'
                ),
            ],
        ];
    }

    private function renderForm(CrawlAction $selectedAction): string
    {
        $requestForm = RequestFormFactory::create(
            $selectedAction,
            $this->view,
            $this->pObj,
            $this->extensionSettings,
            $this->backendModuleMenu
        );
        return $requestForm->render(
            $this->id,
            (string) $this->backendModuleSettings->getDepth(),
            $this->backendModuleMenu['depth']
        );
    }
}
