<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Backend\RequestForm;

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

use AOE\Crawler\Backend\RequestForm\MultiProcessRequestForm;
use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

class MultiProcessRequestFormTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['cms', 'core', 'frontend', 'version', 'lang', 'fluid', 'info'];

    /**
     * @var MultiProcessRequestForm
     */
    protected $multiProcessRequestForm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupExtensionSettings();
        $this->SetupBackendUser();
        $this->setupLanguageService();
        $view = $this->setupView();
        $infoModuleController = GeneralUtility::makeInstance(InfoModuleController::class);
        $extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $this->multiProcessRequestForm = GeneralUtility::makeInstance(MultiProcessRequestForm::class, $view, $infoModuleController, $extensionSettings);
    }

    /**
     * @test
     */
    public function renderWithNoConfigurationSelected(): void
    {
        self::markTestSkipped('Please implement');
    }

    private function setupExtensionSettings(): void
    {
        $configuration = [
            'sleepTime' => '1000',
            'sleepAfterFinish' => '10',
            'countInARun' => '100',
            'purgeQueueDays' => '14',
            'processLimit' => '1',
            'processMaxRunTime' => '300',
            'maxCompileUrls' => '10000',
            'processDebug' => '0',
            'processVerbose' => '0',
            'crawlHiddenPages' => '0',
            'phpPath' => '/usr/bin/php',
            'enableTimeslot' => '1',
            'makeDirectRequests' => '0',
            'frontendBasePath' => '/',
            'cleanUpOldQueueEntries' => '1',
            'cleanUpProcessedAge' => '2',
            'cleanUpScheduledAge' => '7',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = $configuration;
    }

    private function SetupBackendUser(): void
    {
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAdmin', 'getTSConfig', 'getPagePermsClause', 'isInWebMount', 'backendCheckLogin'])
            ->getMock();
    }

    private function setupLanguageService(): void
    {
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
    }

    /**
     * @return object|\Psr\Log\LoggerAwareInterface|\TYPO3\CMS\Core\SingletonInterface|StandaloneView
     */
    private function setupView()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([__DIR__ . '/../../Fixtures/Resources/Layouts/']);
        $view->setTemplateRootPaths([__DIR__ . '/../../Fixtures/Resources/Templates/']);
        return $view;
    }
}
