<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Controller\Backend;

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

use AOE\Crawler\Controller\Backend\BackendModuleStartCrawlingController;
use AOE\Crawler\Controller\CrawlerController;
use AOE\Crawler\Tests\Functional\BackendRequestTestTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModuleStartCrawlingControllerTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;
    /**
     * @test
     */
    public function checkResponseOfHandleRequest(): void
    {
        $this->setupBackendRequest();
        // Set extension settings
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpBinary' => 'php',
        ];

        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAdmin', 'getTSConfig', 'getPagePermsClause', 'isInWebMount', 'backendCheckLogin'])
            ->getMock();

        $mockedCrawlerController = $this->createPartialMock(CrawlerController::class, ['dummy']);
        $subject = GeneralUtility::makeInstance(BackendModuleStartCrawlingController::class, $mockedCrawlerController);

        //$serverRequest = new ServerRequest();
        $response = $subject->handleRequest($GLOBALS['TYPO3_REQUEST']);

        self::assertEquals(
            'test',
            $response->getBody()
        );
    }
}
