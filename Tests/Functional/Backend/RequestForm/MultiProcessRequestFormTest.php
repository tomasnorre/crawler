<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Backend\RequestForm;

/*
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

use AOE\Crawler\Backend\RequestForm\MultiProcessRequestForm;
use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Service\ProcessService;
use AOE\Crawler\Tests\Functional\LanguageServiceTestTrait;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

class MultiProcessRequestFormTest extends FunctionalTestCase
{
    use LanguageServiceTestTrait;
    use ProphecyTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    protected MultiProcessRequestForm $multiProcessRequestForm;

    protected function setUp(): void
    {
        parent::setUp();

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
            $infoModuleController = $this->createMock(InfoModuleController::class);
        }
        $extensionSettings = GeneralUtility::makeInstance(
            ExtensionConfigurationProvider::class
        )->getExtensionConfiguration();
        $processService = $this->prophesize(ProcessService::class);

        $this->multiProcessRequestForm = GeneralUtility::makeInstance(
            MultiProcessRequestForm::class,
            $view,
            $infoModuleController,
            $extensionSettings,
            $processService->reveal()
        );
    }

    /**
     * @test
     */
    public function renderWithNoPageSelected(): void
    {
        self::markTestSkipped('Please implement');
        /*self::assertStringContainsString(
            'Please select a page in the pagetree ',
            $this->multiProcessRequestForm->render(0, '', [])
        );*/
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
