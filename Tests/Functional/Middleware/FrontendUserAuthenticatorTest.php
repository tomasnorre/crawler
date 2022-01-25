<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Functional\Middleware;

/*
 * (c) 2022 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Middleware\FrontendUserAuthenticator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @covers \AOE\Crawler\Middleware\FrontendUserAuthenticator
 */
class FrontendUserAuthenticatorTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    /**
     * @var FrontendUserAuthenticator
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(FrontendUserAuthenticator::class);
        $this->importDataSet(__DIR__ . '/Fixtures/ProcessHandlesFeGroups/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/ProcessHandlesFeGroups/tt_content.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/ProcessHandlesFeGroups/tx_crawler_queue.xml');
    }

    /**
     * @test
     */
    public function processReturnExpectedResponse(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-T3CRAWLER')->willReturn('1006:28f6fd71036abbe3452a0bf9ca10ee38');

        $handlerResponse = new Response();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($handlerResponse);

        $response = $this->subject->process($request->reveal(), $handler->reveal());

        self::assertEquals(
            200,
            $response->getStatusCode()
        );
    }

}
