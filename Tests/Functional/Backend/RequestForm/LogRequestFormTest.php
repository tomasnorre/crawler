<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Backend\RequestForm;

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Backend\RequestForm\LogRequestForm;
use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

class LogRequestFormTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected \AOE\Crawler\Backend\RequestForm\LogRequestForm $logRequestForm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../../data/pages.xml');

        $this->setupExtensionSettings();
        $this->SetupBackendUser();
        $this->setupLanguageService();
        $view = $this->setupView();
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion() === 10) {
            $infoModuleController = GeneralUtility::makeInstance(
                InfoModuleController::class,
                $this->prophesize(ModuleTemplate::class)->reveal(),
                $this->prophesize(UriBuilder::class)->reveal(),
                $this->prophesize(FlashMessageService::class)->reveal(),
                $this->prophesize(ContainerInterface::class)->reveal()
            );
        } else {
            // version 11+
            $infoModuleController = GeneralUtility::makeInstance(
                InfoModuleController::class,
                $this->prophesize(IconFactory::class)->reveal(),
                $this->prophesize(PageRenderer::class)->reveal(),
                $this->prophesize(UriBuilder::class)->reveal(),
                $this->prophesize(FlashMessageService::class)->reveal(),
                $this->prophesize(ContainerInterface::class)->reveal(),
                $this->prophesize(ModuleTemplateFactory::class)->reveal()
            );
        }
        $extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();
        $this->logRequestForm = GeneralUtility::makeInstance(LogRequestForm::class, $view, $infoModuleController, $extensionSettings);
    }

    /**
     * @test
     */
    public function render(): void
    {
        $this->markTestSkipped('Please implement');
    }

    /**
     * @test
     */
    public function renderWithNoPageSelected(): void
    {
        //self::markTestSkipped('Please implement');
        self::assertStringContainsString(
            'Please select a page in the pagetree',
            $this->logRequestForm->render(0, '', [])
        );
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
    private function setupView(): \Psr\Log\LoggerAwareInterface|\TYPO3\CMS\Core\SingletonInterface|\TYPO3\CMS\Fluid\View\StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([__DIR__ . '/../../Fixtures/Resources/Layouts/']);
        $view->setTemplateRootPaths([__DIR__ . '/../../Fixtures/Resources/Templates/']);
        return $view;
    }
}
