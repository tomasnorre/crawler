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

use AOE\Crawler\Middleware\CrawlerInitialization;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * @covers \AOE\Crawler\Middleware\CrawlerInitialization
 */
class CrawlerInitializationTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        $this->subject = GeneralUtility::makeInstance(CrawlerInitialization::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfe->reveal();
        $GLOBALS['TSFE']->id = random_int(0,10000);
    }

    /**
     * @test
     * @dataProvider processSetsTSFEApplicationDataDataProvider
     */
    public function processSetsTSFEApplicationData(string $feGroups, array $expectedGroups): void
    {
        self::assertNull($GLOBALS['TSFE']->applicationData['forceIndexing']);

        $queueParameters = [
            'url' => 'https://crawler-devbox.ddev.site',
            'feUserGroupList' => $feGroups,
            'procInstructions' => [''],
            'procInstrParams' => [],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('tx_crawler')->willReturn($queueParameters);

        $handlerResponse = new Response();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($handlerResponse);

        $response = $this->subject->process($request->reveal(), $handler->reveal());

        self::assertTrue($GLOBALS['TSFE']->applicationData['forceIndexing']);
        self::assertTrue($GLOBALS['TSFE']->applicationData['tx_crawler']['running']);
        self::assertEquals($queueParameters, $GLOBALS['TSFE']->applicationData['tx_crawler']['parameters']);
        self::assertEquals($expectedGroups, $GLOBALS['TSFE']->applicationData['tx_crawler']['log']);

        self::assertArrayHasKey('id', $GLOBALS['TSFE']->applicationData['tx_crawler']['vars']);
        self::assertArrayHasKey('gr_list', $GLOBALS['TSFE']->applicationData['tx_crawler']['vars']);
        self::assertArrayHasKey('no_cache', $GLOBALS['TSFE']->applicationData['tx_crawler']['vars']);

        self::assertTrue($response->hasHeader('X-T3Crawler-Meta'));
    }

    public function processSetsTSFEApplicationDataDataProvider(): iterable
    {
        yield 'FE Groups set' => [
            'feGroups' => '1,2',
            'expectedGroups' => ['User Groups: 1,2'],
        ];

        yield 'No FE Groups set' => [
            'feGroups' => '',
            'expectedGroups' => ['User Groups: '],
        ];
    }
}
