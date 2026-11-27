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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageInformationCreationFailedException;
use TYPO3\CMS\Frontend\Page\PageInformationFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Middleware\CrawlerInitialization::class)]
class CrawlerInitializationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[\PHPUnit\Framework\Attributes\Test]
    public function processRequestNotHandled(): void
    {
        $subject = self::getAccessibleMock(CrawlerInitialization::class, null, [], '', false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getAttribute')->with('tx_crawler')->willReturn(null);

        $handlerResponse = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with(self::identicalTo($request))->willReturn(
            $handlerResponse
        );

        $response = $subject->process($request, $handler);

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function processReturnsResponseOfFailedPageInformationCreation(): void
    {
        $errorResponse = new Response('php://temp', 404);
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willThrowException(
            new PageInformationCreationFailedException($errorResponse, 1_764_201_600)
        );
        $pageInformationFactory = (new \ReflectionClass(
            PageInformationFactory::class
        ))->newInstanceWithoutConstructor();
        // The factory is final, so its event dispatcher makes create() fail
        \Closure::bind(
            static function (PageInformationFactory $factory) use ($eventDispatcher): void {
                $factory->eventDispatcher = $eventDispatcher;
            },
            null,
            PageInformationFactory::class
        )($pageInformationFactory);
        $subject = new CrawlerInitialization($pageInformationFactory, new Context());
        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('tx_crawler', [
                'url' => 'https://example.com/',
            ])
            ->withAttribute('routing', new PageArguments(5, '0', []));
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        self::assertSame($errorResponse, $subject->process($request, $handler));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function processOmitsCrawlerMetaHeaderIfDataCannotBeJsonEncoded(): void
    {
        $context = new Context();
        $context->setAspect('frontend.user', new UserAspect(null, [0, -2]));
        $subject = new CrawlerInitialization(
            (new \ReflectionClass(PageInformationFactory::class))->newInstanceWithoutConstructor(),
            $context
        );
        $pageInformation = new PageInformation();
        $pageInformation->setId(5);
        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('tx_crawler', [
                // Invalid UTF-8 cannot be JSON encoded
                'url' => "https://example.com/\xB1\x31",
            ])
            ->withAttribute('frontend.page.information', $pageInformation);
        $handlerResponse = new Response();
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($handlerResponse);

        $response = $subject->process($request, $handler);

        self::assertSame($handlerResponse, $response);
        self::assertFalse($response->hasHeader('X-T3Crawler-Meta'));
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
