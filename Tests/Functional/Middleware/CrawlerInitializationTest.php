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
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Middleware\CrawlerInitialization::class)]
class CrawlerInitializationTest extends FunctionalTestCase
{
    use ProphecyTrait;

    private CrawlerInitialization $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(CrawlerInitialization::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfe->reveal();
        $GLOBALS['TSFE']->id = random_int(0, 10000);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('processSetsTSFEApplicationDataDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function processSetsTSFEApplicationData(string $feGroups, array $expectedGroups): void
    {
        self::assertEmpty($GLOBALS['TSFE']->applicationData);

        $queueParameters = [
            'url' => 'https://crawler-devbox.ddev.site',
            'feUserGroupList' => $feGroups,
            'procInstructions' => [''],
            'procInstrParams' => [],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('tx_crawler')->willReturn($queueParameters);
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion() >= 13) {
            $request->getAttribute('frontend.cache.instruction')->willReturn(
                new \TYPO3\CMS\Frontend\Cache\CacheInstruction()
            );
        }

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

    public static function processSetsTSFEApplicationDataDataProvider(): iterable
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
