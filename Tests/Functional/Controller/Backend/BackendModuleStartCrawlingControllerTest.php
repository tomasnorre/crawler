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
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModuleStartCrawlingControllerTest extends FunctionalTestCase
{
    use BackendRequestTestTrait;
    use ProphecyTrait;

    protected array $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @test
     */
    public function checkResponseOfHandleRequest(): never
    {
        //$this->markTestSkipped('WIP');
        $this->setupBackendRequest();

        // Set extension settings
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crawler'] = [
            'phpBinary' => 'php',
        ];

        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAdmin', 'getTSConfig', 'getPagePermsClause', 'isInWebMount', 'backendCheckLogin'])
            ->getMock();

        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);

        $mockedCrawlerController = $this->getAccessibleMock(CrawlerController::class, ['dummy']);

        $mockedCrawlerLogUri = new Uri();
        $mockedCrawlerLogUri->withScheme('https')->withHost('crawler.tld');
        $mockedBackendUriBuilder = $this->createPartialMock(UriBuilder::class, ['buildUriFromRoute']);
        $mockedBackendUriBuilder->expects($this->once())->method('buildUriFromRoute')->with('web_site_crawler_log', ['id' => -1])->willReturn($mockedCrawlerLogUri);
        $subject = GeneralUtility::makeInstance(BackendModuleStartCrawlingController::class, $mockedCrawlerController, $mockedBackendUriBuilder);

        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('path', ['packageName' => 'tomasnorre/crawler']));

        $GLOBALS['TYPO3_REQUEST'] = $request;

        //dd($request);

        /**
         * Have a look in core tests. The "Request" object needs prober setup (which is normally handled by the middleware stack I think).
        $request = (new ServerRequest('https://example.com/typo3/'))
        ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']));
        // your needed stuff for the request object, params etc
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $controller->handleRequest($request);
        the request object needs to have the route attribute, with the packageName of the extension (composer package ame). Maybe add that to your
         * request object you hand over to the controller request handle method (edited)
        with the package name, default kicks in (extension default template files -> ext:<ext>/Resources/Private/(Templates/Partials/Layouts)/` ...
         * would bet the missing route/packagename is the missing puzzle piece
         */

        $response = $subject->handleRequest($request);
        self::assertEquals('test', $response->getBody());
    }
}
