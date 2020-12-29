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

use AOE\Crawler\Backend\RequestForm\LogRequestForm;
use AOE\Crawler\Backend\RequestForm\MultiProcessRequestForm;
use AOE\Crawler\Backend\RequestForm\RequestFormFactory;
use AOE\Crawler\Backend\RequestForm\StartRequestForm;
use AOE\Crawler\Value\CrawlAction;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

class RequestFormFactoryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function createReturnsExpectedObject(): void
    {
        $this->setupExtensionSettings();

        $view = $this->createPartialMock(StandaloneView::class, ['dummy']);
        $infoModule = $this->createPartialMock(InfoModuleController::class, ['dummy']);

        $crawlActionLog = new CrawlAction('log');
        self::assertInstanceOf(
            LogRequestForm::class,
            RequestFormFactory::create(
                $crawlActionLog,
                $view,
                $infoModule,
                []
            )
        );

        $crawlActionLog = new CrawlAction('multiprocess');
        self::assertInstanceOf(
            MultiProcessRequestForm::class,
            RequestFormFactory::create(
                $crawlActionLog,
                $view,
                $infoModule,
                []
            )
        );

        $crawlActionLog = new CrawlAction('start');
        self::assertInstanceOf(
            StartRequestForm::class,
            RequestFormFactory::create(
                $crawlActionLog,
                $view,
                $infoModule,
                []
            )
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
}
