<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Middleware;

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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageInformationFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Middleware\CrawlerInitialization::class)]
class CrawlerInitializationTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    #[\PHPUnit\Framework\Attributes\Test]
    public function processRequestNotHandled(): void
    {
        $subject = self::getAccessibleMock(CrawlerInitialization::class, null, [], '', false);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('tx_crawler')->willReturn(null);

        $handlerResponse = new Response();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($handlerResponse);

        $response = $subject->process($request->reveal(), $handler->reveal());

        self::assertSame($handlerResponse, $response);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('processAddsCrawlerMetaHeaderDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function processAddsCrawlerMetaHeaderToResponse(
        array $queueParameters,
        ?bool $cachingAllowed,
        array $expectedMeta
    ): void {
        $context = new Context();
        $context->setAspect('frontend.user', new UserAspect(null, [0, -2, 1]));
        $subject = new CrawlerInitialization(
            (new \ReflectionClass(PageInformationFactory::class))->newInstanceWithoutConstructor(),
            $context
        );
        $pageInformation = new PageInformation();
        $pageInformation->setId(5);
        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('tx_crawler', $queueParameters)
            ->withAttribute('frontend.page.information', $pageInformation);
        // null: the request has no cache instruction
        if ($cachingAllowed !== null) {
            $cacheInstruction = new CacheInstruction();
            if (!$cachingAllowed) {
                $cacheInstruction->disableCache('Testing');
            }
            $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
        }
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturnCallback(
            static function (ServerRequestInterface $request) use ($queueParameters): ResponseInterface {
                self::assertSame([
                    'forceIndexing' => true,
                    'running' => true,
                    'parameters' => $queueParameters,
                    'log' => ['User Groups: ' . ($queueParameters['feUserGroupList'] ?? '')],
                ], $request->getAttribute('tx_crawler'));
                return new Response();
            }
        );

        $response = $subject->process($request, $handler);

        self::assertSame($expectedMeta, json_decode($response->getHeaderLine('X-T3Crawler-Meta'), true));
    }

    public static function processAddsCrawlerMetaHeaderDataProvider(): iterable
    {
        yield 'Without user groups' => [
            [
                'url' => 'https://example.com/',
            ],
            true,
            [
                'forceIndexing' => true,
                'running' => true,
                'parameters' => [
                    'url' => 'https://example.com/',
                ],
                'log' => ['User Groups: '],
                'vars' => [
                    'id' => 5,
                    'gr_list' => '0,-2,1',
                    'no_cache' => false,
                ],
            ],
        ];
        yield 'Without cache instruction' => [
            [
                'url' => 'https://example.com/',
            ],
            null,
            [
                'forceIndexing' => true,
                'running' => true,
                'parameters' => [
                    'url' => 'https://example.com/',
                ],
                'log' => ['User Groups: '],
                'vars' => [
                    'id' => 5,
                    'gr_list' => '0,-2,1',
                    'no_cache' => false,
                ],
            ],
        ];
        yield 'With user groups and disabled cache' => [
            [
                'url' => 'https://example.com/',
                'feUserGroupList' => '1',
            ],
            false,
            [
                'forceIndexing' => true,
                'running' => true,
                'parameters' => [
                    'url' => 'https://example.com/',
                    'feUserGroupList' => '1',
                ],
                'log' => ['User Groups: 1'],
                'vars' => [
                    'id' => 5,
                    'gr_list' => '0,-2,1',
                    'no_cache' => true,
                ],
            ],
        ];
    }
}
