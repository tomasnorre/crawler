<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use AOE\Crawler\Backend\RequestForm\RequestFormFactory;
use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use AOE\Crawler\Domain\Repository\QueueRepository;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Value\CrawlAction;
use AOE\Crawler\Value\ModuleSettings;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

/**
 * Function for Info module, containing three main actions:
 * - List of all queued items
 * - Log functionality
 * - Process overview
 */
class BackendModule
{
    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    /**
     * The current page ID
     * @var int
     */
    protected $id;

    // Internal, dynamic:

    /**
     * @var array
     */
    protected $duplicateTrack = [];

    /**
     * @var bool
     */
    protected $submitCrawlUrls = false;

    /**
     * @var bool
     */
    protected $downloadCrawlUrls = false;

    /**
     * @var int
     */
    protected $scheduledTime = 0;

    /**
     * @var int
     */
    protected $reqMinute = 1000;

    /**
     * @var array holds the selection of configuration from the configuration selector box
     */
    protected $incomingConfigurationSelection = [];

    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    /**
     * @var array
     */
    protected $CSVaccu = [];

    /**
     * If true the user requested a CSV export of the queue
     *
     * @var boolean
     */
    protected $CSVExport = false;

    /**
     * @var array
     */
    protected $downloadUrls = [];

    /**
     * Holds the configuration from ext_conf_template loaded by getExtensionConfiguration()
     *
     * @var array
     */
    protected $extensionSettings = [];

    /**
     * Indicate that an flash message with an error is present.
     *
     * @var boolean
     */
    protected $isErrorDetected = false;

    /**
     * @var ProcessService
     */
    protected $processManager;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var QueueRepository
     */
    protected $queueRepository;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var JsonCompatibilityConverter
     */
    protected $jsonCompatibilityConverter;

    /**
     * @var LanguageService
     */
    private $languageService;

    /**
     * @var ModuleSettings
     */
    private $moduleSettings;

    public function __construct()
    {
        $this->languageService = $GLOBALS['LANG'];
        $objectManger = GeneralUtility::makeInstance(ObjectManager::class);
        $this->processManager = $objectManger->get(ProcessService::class);
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_crawler_queue');
        $this->queueRepository = $objectManger->get(QueueRepository::class);
        $this->initializeView();
        $this->extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->jsonCompatibilityConverter = GeneralUtility::makeInstance(JsonCompatibilityConverter::class);
    }

    /**
     * Called by the InfoModuleController
     */
    public function init(InfoModuleController $pObj): void
    {
        $this->pObj = $pObj;
        $this->id = (int) GeneralUtility::_GP('id');
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->getModuleMenu());
        $this->moduleSettings = ModuleSettings::fromArray($this->pObj->MOD_SETTINGS);
    }

    /**
     * Additions to the function menu array
     *
     * @return array Menu array
     * @deprecated Using BackendModule->modMenu() is deprecated since 9.1.1 and will be removed in v11.x
     */
    public function modMenu(): array
    {
        return $this->getModuleMenu();
    }

    public function main(): string
    {
        if (empty($this->pObj->MOD_SETTINGS['processListMode'])) {
            $this->pObj->MOD_SETTINGS['processListMode'] = 'simple';
        }
        $this->view->assign('currentPageId', $this->id);

        $selectedAction = new CrawlAction($this->pObj->MOD_SETTINGS['crawlaction'] ?? 'start');

        // Type function menu:
        $actionDropdown = BackendUtility::getFuncMenu(
            $this->id,
            'SET[crawlaction]',
            $selectedAction,
            $this->pObj->MOD_MENU['crawlaction']
        );

        $theOutput = '<h2>' . htmlspecialchars($this->getLanguageService()->getLL('title'), ENT_QUOTES | ENT_HTML5) . '</h2>' . $actionDropdown;
        $theOutput .= $this->renderForm($selectedAction);

        return $theOutput;
    }

    /*****************************
     *
     * General Helper Functions
     *
     *****************************/

    private function initializeView(): void
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:crawler/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:crawler/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:crawler/Resources/Private/Templates/Backend']);
        $view->getRequest()->setControllerExtensionName('Crawler');
        $this->view = $view;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getModuleMenu(): array
    {
        return [
            'depth' => [
                0 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                99 => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
            'crawlaction' => [
                'start' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.start'),
                'log' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.log'),
                'multiprocess' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.multiprocess'),
            ],
            'log_resultLog' => '',
            'log_feVars' => '',
            'processListMode' => '',
            'log_display' => [
                'all' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.all'),
                'pending' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.pending'),
                'finished' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.finished'),
            ],
            'itemsPerPage' => [
                '5' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.5'),
                '10' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.10'),
                '50' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.50'),
                '0' => $this->getLanguageService()->sL('LLL:EXT:crawler/Resources/Private/Language/locallang.xlf:labels.itemsPerPage.0'),
            ],
        ];
    }

    private function renderForm(CrawlAction $selectedAction): string
    {
        $requestForm = RequestFormFactory::create($selectedAction, $this->view, $this->moduleSettings, $this->pObj);
        return $requestForm->render(
            $this->id,
            $this->pObj->MOD_SETTINGS['depth'],
            $this->pObj->MOD_MENU['depth']
        );
    }
}
