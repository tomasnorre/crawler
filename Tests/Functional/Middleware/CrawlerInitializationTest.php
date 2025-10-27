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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(CrawlerInitialization::class)]
class CrawlerInitializationTest extends FunctionalTestCase
{
    use ProphecyTrait;

    private CrawlerInitialization $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(CrawlerInitialization::class);
    }

    #[DataProvider('processSetsCrawlerDataDataProvider')]
    #[Test]
    public function processSetsCrawlerData(string $feGroups, array $expectedGroups): void
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion() < 13) {
            $this->markTestSkipped('Only tested with TYPO3 13+');
        }

        $queueParameters = [
            'url' => 'https://crawler-devbox.ddev.site',
            'feUserGroupList' => $feGroups,
            'procInstructions' => [''],
            'procInstrParams' => [],
        ];

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('tx_crawler')->willReturn($queueParameters);

        $request->getAttribute('frontend.cache.instruction')->willReturn(
            new CacheInstruction()
        );

        $request->withAttribute('tx_crawler', [
            'forceIndexing' => true,
            'running' => true,
            'parameters' => $queueParameters,
            'log' => ['User Groups: ' . ($queueParameters['feUserGroupList'] ?? '')],
        ])->willReturn();

        $handlerResponse = new Response();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($handlerResponse);

        $response = $this->subject->process($request->reveal(), $handler->reveal());

        self::assertTrue($response->hasHeader('X-T3Crawler-Meta'));
        $meta = unserialize($response->getHeaderLine('X-T3Crawler-Meta'));

        self::assertTrue($meta['forceIndexing']);
        self::assertTrue($meta['running']);
        self::assertEquals($queueParameters, $meta['parameters']);
        self::assertEquals($expectedGroups, $meta['log']);

        self::assertArrayHasKey('id', $meta['vars']);
        self::assertArrayHasKey('gr_list', $meta['vars']);
        self::assertArrayHasKey('no_cache', $meta['vars']);
    }

    public static function processSetsCrawlerDataDataProvider(): iterable
    {
        yield 'FE Groups set' => [
            'feGroups' => '1,2',
            'expectedGroups' => ['User Groups: 1,2'],
        ];

        /*yield 'No FE Groups set' => [
            'feGroups' => '',
            'expectedGroups' => ['User Groups: '],
        ];*/
    }
}
