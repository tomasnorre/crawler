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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageInformationFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(CrawlerInitialization::class)]
class CrawlerInitializationTest extends FunctionalTestCase
{
    private CrawlerInitialization $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $pageInformationFactory = $this->get(PageInformationFactory::class);
        $this->subject = new CrawlerInitialization($pageInformationFactory, $this->get(Context::class));
    }

    #[DataProvider('processSetsCrawlerDataDataProvider')]
    #[Test]
    public function processSetsCrawlerData(string $feGroups, array $expectedGroups): void
    {
        $queueParameters = [
            'url' => 'https://crawler-devbox.ddev.site',
            'feUserGroupList' => $feGroups,
            'procInstructions' => [''],
            'procInstrParams' => [],
        ];

        $pageInformation = new PageInformation();
        $pageInformation->setId(1234);

        $request = (new ServerRequest('https://crawler-devbox.ddev.site/'))
            ->withAttribute('tx_crawler', $queueParameters)
            ->withAttribute('frontend.cache.instruction', new CacheInstruction())
            ->withAttribute('frontend.page.information', $pageInformation);

        $expectedCrawlerAttribute = [
            'forceIndexing' => true,
            'running' => true,
            'parameters' => $queueParameters,
            'log' => ['User Groups: ' . $queueParameters['feUserGroupList']],
        ];

        $handlerResponse = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with(
            self::callback(
                static fn (ServerRequestInterface $handledRequest): bool => $handledRequest->getAttribute(
                    'tx_crawler'
                ) === $expectedCrawlerAttribute
            )
        )->willReturn($handlerResponse);

        $response = $this->subject->process($request, $handler);

        self::assertTrue($response->hasHeader('X-T3Crawler-Meta'));
        $meta = json_decode($response->getHeaderLine('X-T3Crawler-Meta'), true);

        self::assertTrue($meta['forceIndexing']);
        self::assertTrue($meta['running']);
        self::assertEquals($queueParameters, $meta['parameters']);
        self::assertEquals($expectedGroups, $meta['log']);

        self::assertArrayHasKey('id', $meta['vars']);
        self::assertEquals(1234, $meta['vars']['id']);
        self::assertArrayHasKey('gr_list', $meta['vars']);
        self::assertArrayHasKey('no_cache', $meta['vars']);
    }

    public static function processSetsCrawlerDataDataProvider(): iterable
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
