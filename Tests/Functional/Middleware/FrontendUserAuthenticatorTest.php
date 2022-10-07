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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * @covers \AOE\Crawler\Middleware\FrontendUserAuthenticator
 */
class FrontendUserAuthenticatorTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/crawler'];

    private \AOE\Crawler\Middleware\FrontendUserAuthenticator $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(FrontendUserAuthenticator::class);
    }

    /**
     * @test
     */
    public function processQueueEntryNotFound(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-T3CRAWLER')->willReturn('404:entry-not-found');
        $request->getHeaderLine('Accept')->willReturn('');
        $request->getAttribute('site')->willReturn('');

        $handlerResponse = new Response();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($handlerResponse);

        $response = $this->subject->process($request->reveal(), $handler->reveal());

        self::assertStringContainsString(
            'No crawler entry found',
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     * @dataProvider processSetsExpectedUserGroupsDataProvider
     */
    public function processSetsExpectedUserGroups(string $feGroups, string $headerLine): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/ProcessHandlesFeGroups/tx_crawler_queue.xml');

        $queueParametersArray = [
            'url' => 'https://crawler-devbox.ddev.site',
            'feUserGroupList' => $feGroups,
            'procInstructions' => [''],
            'procInstrParams' => [],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-T3CRAWLER')->willReturn($headerLine);
        $crawlerRequest = $this->prophesize(ServerRequestInterface::class);
        $crawlerRequest->getAttribute('frontend.user')->willReturn($this->prophesize(FrontendUserAuthentication::class));
        $request->withAttribute('tx_crawler', $queueParametersArray)->willReturn($crawlerRequest);
        $request->withAttribute('tx_crawler', false)->willReturn($crawlerRequest);

        $handlerResponse = new Response();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($crawlerRequest->reveal())->willReturn($handlerResponse);

        $response = $this->subject->process($request->reveal(), $handler->reveal());

        $feGroupsArray = explode(',', $feGroups);

        foreach ($feGroupsArray as $feGroup) {
            self::assertContains(
                $feGroup,
                $this->subject->getContext()->getAspect('frontend.user')->get('groupIds')
            );
        }

        self::assertEquals(
            200,
            $response->getStatusCode()
        );
    }

    public function processSetsExpectedUserGroupsDataProvider(): iterable
    {
        yield 'One FE Group' => [
            'feGroups' => '1',
            'headerLine' => '1006:28f6fd71036abbe3452a0bf9ca10ee38',
        ];

        yield 'Two FE Groups' => [
            'feGroups' => '1,2',
            'headerLine' => '1007:8e6edae3da393a9412898ef59e6cf925',
        ];
    }
}
