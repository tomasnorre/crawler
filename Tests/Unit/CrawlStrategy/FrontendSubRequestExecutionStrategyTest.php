<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

/*
 * (c) 2026 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\CrawlStrategy\FrontendSubRequestExecutionStrategy;
use AOE\Crawler\Tests\Unit\FrozenClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Frontend\Http\Application;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(FrontendSubRequestExecutionStrategy::class)]
class FrontendSubRequestExecutionStrategyTest extends UnitTestCase
{
    private const CRAWLER_ID = '1006:8e6edae3da393a9412898ef59e6cf925';
    private const NOW = 1763640042;

    private Application&MockObject $application;
    private Context $context;
    private PageRenderer&MockObject $pageRenderer;
    private LoggerInterface&MockObject $logger;

    /**
     * @var list<ServerRequestInterface>
     */
    private array $handledRequests = [];

    private array $globalsBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['TYPO3_REQUEST', 'EXEC_TIME', 'SIM_EXEC_TIME', 'ACCESS_TIME', 'SIM_ACCESS_TIME'] as $name) {
            if (array_key_exists($name, $GLOBALS)) {
                $this->globalsBackup[$name] = $GLOBALS[$name];
            }
        }
        $this->application = $this->createMock(Application::class);
        $this->context = new Context();
        $this->pageRenderer = $this->createMock(PageRenderer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TYPO3_REQUEST'],
            $GLOBALS['EXEC_TIME'],
            $GLOBALS['SIM_EXEC_TIME'],
            $GLOBALS['ACCESS_TIME'],
            $GLOBALS['SIM_ACCESS_TIME']
        );
        foreach ($this->globalsBackup as $name => $value) {
            $GLOBALS[$name] = $value;
        }
        parent::tearDown();
    }

    public static function fetchUrlContentsReturnsFalseForUrlsWithoutHttpDataProvider(): iterable
    {
        yield 'no scheme' => [
            'url' => new Uri('not-an-url'),
        ];
        yield 'websocket' => [
            'url' => new Uri('ws://example.com/'),
        ];
        yield 'no host' => [
            'url' => (new Uri('/path'))->withScheme('https'),
        ];
    }

    #[DataProvider('fetchUrlContentsReturnsFalseForUrlsWithoutHttpDataProvider')]
    #[Test]
    public function fetchUrlContentsReturnsFalseForUrlsWithoutHttp(Uri $url): void
    {
        $this->application->expects(self::never())->method('handle');
        $this->logger->expects(self::once())->method('debug')->with(
            sprintf('Scheme does not match for url "%s"', $url),
            [
                'crawlerId' => self::CRAWLER_ID,
            ]
        );

        self::assertFalse($this->createSubject()->fetchUrlContents($url, self::CRAWLER_ID));
    }

    #[Test]
    public function fetchUrlContentsHandsCrawlerRequestToFrontendApplication(): void
    {
        $this->respondWith(new Response());

        $this->createSubject()->fetchUrlContents(
            new Uri('https://user:p%40ss@example.com:8443/page?id=12&foo[bar]=baz'),
            self::CRAWLER_ID
        );

        self::assertCount(1, $this->handledRequests);
        $request = $this->handledRequests[0];
        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://example.com:8443/page?id=12&foo%5Bbar%5D=baz', (string) $request->getUri());
        self::assertSame(self::CRAWLER_ID, $request->getHeaderLine('X-T3Crawler'));
        self::assertSame('TYPO3 crawler', $request->getHeaderLine('User-Agent'));
        self::assertSame('Basic ' . base64_encode('user:p@ss'), $request->getHeaderLine('Authorization'));
        self::assertSame([
            'id' => '12',
            'foo' => [
                'bar' => 'baz',
            ],
        ], $request->getQueryParams());
        self::assertSame([
            'HTTP_HOST' => 'example.com:8443',
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => '8443',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'SCRIPT_FILENAME' => Environment::getPublicPath() . '/index.php',
            'REQUEST_URI' => '/page?id=12&foo%5Bbar%5D=baz',
            'QUERY_STRING' => 'id=12&foo%5Bbar%5D=baz',
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'TYPO3 crawler',
        ], $request->getServerParams());
    }

    #[Test]
    public function fetchUrlContentsSendsHttpRequestWithoutPortOrAuthorization(): void
    {
        $this->respondWith(new Response());

        $this->createSubject()->fetchUrlContents(new Uri('http://example.com/'), self::CRAWLER_ID);

        $request = $this->handledRequests[0];
        self::assertFalse($request->hasHeader('Authorization'));
        self::assertSame([], $request->getQueryParams());
        $serverParams = $request->getServerParams();
        self::assertSame('example.com', $serverParams['HTTP_HOST']);
        self::assertSame('80', $serverParams['SERVER_PORT']);
        self::assertSame('off', $serverParams['HTTPS']);
        self::assertSame('/', $serverParams['REQUEST_URI']);
        self::assertSame('', $serverParams['QUERY_STRING']);
    }

    #[Test]
    public function fetchUrlContentsUsesPort443ForHttpsWithoutPort(): void
    {
        $this->respondWith(new Response());

        $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertSame('443', $this->handledRequests[0]->getServerParams()['SERVER_PORT']);
    }

    public static function frontendBasePathDataProvider(): iterable
    {
        yield 'not configured' => [
            'settings' => [],
            'expected' => '/index.php',
        ];
        yield 'empty' => [
            'settings' => [
                'frontendBasePath' => '',
            ],
            'expected' => '/index.php',
        ];
        yield 'slash' => [
            'settings' => [
                'frontendBasePath' => '/',
            ],
            'expected' => '/index.php',
        ];
        yield 'without slashes' => [
            'settings' => [
                'frontendBasePath' => 'cms',
            ],
            'expected' => '/cms/index.php',
        ];
        yield 'with slashes' => [
            'settings' => [
                'frontendBasePath' => '/cms/',
            ],
            'expected' => '/cms/index.php',
        ];
        yield 'number' => [
            'settings' => [
                'frontendBasePath' => 5,
            ],
            'expected' => '/5/index.php',
        ];
        yield 'nested' => [
            'settings' => [
                'frontendBasePath' => 'a/cms/',
            ],
            'expected' => '/a/cms/index.php',
        ];
    }

    #[DataProvider('frontendBasePathDataProvider')]
    #[Test]
    public function fetchUrlContentsSetsScriptNameFromFrontendBasePath(array $settings, string $expected): void
    {
        $this->respondWith(new Response());

        $this->createSubject($settings)->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertSame($expected, $this->handledRequests[0]->getServerParams()['SCRIPT_NAME']);
    }

    #[Test]
    public function fetchUrlContentsReturnsCrawlerMetaOfResponse(): void
    {
        $meta = [
            'running' => true,
            'vars' => [
                'id' => 12,
            ],
        ];
        $this->respondWith((new Response())->withHeader('X-T3Crawler-Meta', (string) json_encode($meta)));

        self::assertSame(
            $meta,
            $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID)
        );
    }

    #[Test]
    public function fetchUrlContentsReportsResponseWithoutCrawlerMeta(): void
    {
        $this->respondWith((new Response())->withHeader('X-T3Crawler-Meta', 'not json'));

        self::assertSame(
            [
                'errorlog' => ['Response has no X-T3Crawler-Meta header'],
                'vars' => [
                    'status' => '200 OK',
                ],
            ],
            $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID)
        );
    }

    public static function fetchUrlContentsReportsErrorStatusDataProvider(): iterable
    {
        yield 'not found' => [
            'status' => 404,
            'expected' => '404 Not Found',
        ];
        yield 'bad request' => [
            'status' => 400,
            'expected' => '400 Bad Request',
        ];
        yield 'server error' => [
            'status' => 503,
            'expected' => '503 Service Unavailable',
        ];
    }

    #[DataProvider('fetchUrlContentsReportsErrorStatusDataProvider')]
    #[Test]
    public function fetchUrlContentsReportsErrorStatus(int $status, string $expected): void
    {
        // An error page has no crawler meta data worth reporting
        $this->respondWith((new Response('php://temp', $status))->withHeader('X-T3Crawler-Meta', '{"running":true}'));
        $this->logger->expects(self::once())->method('debug')->with(
            sprintf('Error while opening "https://example.com/" - %s', $expected),
            [
                'crawlerId' => self::CRAWLER_ID,
            ]
        );

        self::assertSame(
            [
                'errorlog' => [$expected],
            ],
            $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID)
        );
    }

    #[Test]
    public function fetchUrlContentsReportsExceptionOfFrontendApplication(): void
    {
        $this->application->method('handle')->willThrowException(new \RuntimeException('Rendering failed', 1));
        $this->logger->expects(self::once())->method('debug')->with(
            'Error while opening "https://example.com/" - Rendering failed',
            [
                'crawlerId' => self::CRAWLER_ID,
            ]
        );

        self::assertSame(
            [
                'errorlog' => ['Rendering failed'],
            ],
            $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID)
        );
    }

    public static function fetchUrlContentsFollowsRedirectsDataProvider(): iterable
    {
        yield 'relative location' => [
            'location' => '/en/',
            'expected' => 'https://example.com:8443/en/',
        ];
        yield 'absolute location' => [
            'location' => 'http://other.example.com/fr/?a=1',
            'expected' => 'http://other.example.com/fr/?a=1',
        ];
    }

    #[DataProvider('fetchUrlContentsFollowsRedirectsDataProvider')]
    #[Test]
    public function fetchUrlContentsFollowsRedirects(string $location, string $expected): void
    {
        $meta = [
            'running' => true,
        ];
        $this->respondWith(
            (new Response('php://temp', 307))->withHeader('Location', $location),
            (new Response())->withHeader('X-T3Crawler-Meta', (string) json_encode($meta))
        );

        $result = $this->createSubject()->fetchUrlContents(new Uri('https://example.com:8443/'), self::CRAWLER_ID);

        self::assertSame($meta, $result);
        self::assertCount(2, $this->handledRequests);
        self::assertSame($expected, (string) $this->handledRequests[1]->getUri());
        self::assertSame(self::CRAWLER_ID, $this->handledRequests[1]->getHeaderLine('X-T3Crawler'));
    }

    public static function fetchUrlContentsDoesNotFollowDataProvider(): iterable
    {
        yield 'redirect without location' => [
            'status' => 301,
            'location' => '',
            'expected' => [
                'errorlog' => ['Response has no X-T3Crawler-Meta header'],
                'vars' => [
                    'status' => '301 Moved Permanently',
                ],
            ],
        ];
        yield 'redirect to websocket' => [
            'status' => 302,
            'location' => 'ws://example.com/',
            'expected' => [
                'errorlog' => ['Response has no X-T3Crawler-Meta header'],
                'vars' => [
                    'status' => '302 Found',
                ],
            ],
        ];
        yield 'redirect to invalid location' => [
            'status' => 302,
            'location' => 'ftp://example.com/',
            'expected' => [
                'errorlog' => ['Response has no X-T3Crawler-Meta header'],
                'vars' => [
                    'status' => '302 Found',
                ],
            ],
        ];
        yield 'location without redirect' => [
            'status' => 201,
            'location' => '/en/',
            'expected' => [
                'errorlog' => ['Response has no X-T3Crawler-Meta header'],
                'vars' => [
                    'status' => '201 Created',
                ],
            ],
        ];
        yield 'location of client error' => [
            'status' => 400,
            'location' => '/en/',
            'expected' => [
                'errorlog' => ['400 Bad Request'],
            ],
        ];
    }

    #[DataProvider('fetchUrlContentsDoesNotFollowDataProvider')]
    #[Test]
    public function fetchUrlContentsDoesNotFollow(int $status, string $location, array $expected): void
    {
        $response = new Response('php://temp', $status);
        if ($location !== '') {
            $response = $response->withHeader('Location', $location);
        }
        $this->respondWith($response);

        $result = $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertCount(1, $this->handledRequests);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function fetchUrlContentsFollowsLastRedirectStatus(): void
    {
        $this->respondWith(
            (new Response('php://temp', 308))->withHeader('Location', '/en/'),
            (new Response())->withHeader('X-T3Crawler-Meta', '[]')
        );

        self::assertSame(
            [],
            $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID)
        );
        self::assertCount(2, $this->handledRequests);
    }

    #[Test]
    public function fetchUrlContentsFollowsFirstRedirectStatus(): void
    {
        $this->respondWith(
            (new Response('php://temp', 300))->withHeader('Location', '/en/'),
            (new Response())->withHeader('X-T3Crawler-Meta', '[]')
        );

        $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertCount(2, $this->handledRequests);
    }

    #[Test]
    public function fetchUrlContentsFollowsFiveRedirectsAtMost(): void
    {
        $redirect = (new Response('php://temp', 302))->withHeader('Location', '/loop');
        $this->respondWith(...array_fill(0, 6, $redirect));

        $result = $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertCount(6, $this->handledRequests);
        self::assertSame([
            'errorlog' => ['Too many redirects, gave up after 5'],
        ], $result);
    }

    #[Test]
    public function fetchUrlContentsFollowsFiveRedirects(): void
    {
        $redirect = (new Response('php://temp', 302))->withHeader('Location', '/next');
        $this->respondWith(...[...array_fill(0, 5, $redirect), (new Response())->withHeader('X-T3Crawler-Meta', '[]')]);

        $result = $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertCount(6, $this->handledRequests);
        self::assertSame([], $result);
    }

    #[Test]
    public function fetchUrlContentsRunsFrontendAtCurrentTime(): void
    {
        $GLOBALS['EXEC_TIME'] = $GLOBALS['SIM_EXEC_TIME'] = 1000;
        $GLOBALS['ACCESS_TIME'] = $GLOBALS['SIM_ACCESS_TIME'] = 960;
        $timesDuringRequest = [];
        $this->application->method('handle')->willReturnCallback(
            static function () use (&$timesDuringRequest): ResponseInterface {
                $timesDuringRequest = [
                    $GLOBALS['EXEC_TIME'],
                    $GLOBALS['SIM_EXEC_TIME'],
                    $GLOBALS['ACCESS_TIME'],
                    $GLOBALS['SIM_ACCESS_TIME'],
                ];
                return new Response();
            }
        );

        $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        $accessTime = self::NOW - self::NOW % 60;
        self::assertSame([self::NOW, self::NOW, $accessTime, $accessTime], $timesDuringRequest);
        self::assertSame(1000, $GLOBALS['EXEC_TIME']);
        self::assertSame(1000, $GLOBALS['SIM_EXEC_TIME']);
        self::assertSame(960, $GLOBALS['ACCESS_TIME']);
        self::assertSame(960, $GLOBALS['SIM_ACCESS_TIME']);
    }

    #[Test]
    public function fetchUrlContentsRestoresGlobalStateOfWorker(): void
    {
        $workerRequest = $this->createStub(ServerRequestInterface::class);
        $GLOBALS['TYPO3_REQUEST'] = $workerRequest;
        $frontendUser = new UserAspect(null, [0, -1]);
        $visibility = new VisibilityAspect();
        $this->context->setAspect('frontend.user', $frontendUser);
        $this->context->setAspect('visibility', $visibility);
        $this->application->method('handle')->willReturnCallback(
            function (ServerRequestInterface $request): ResponseInterface {
                $GLOBALS['TYPO3_REQUEST'] = $request;
                $this->context->setAspect('frontend.user', new UserAspect(null, [0, -2, 1, 2]));
                $this->context->setAspect('visibility', new VisibilityAspect(true));
                $this->context->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@0')));
                throw new \RuntimeException('Rendering failed', 1);
            }
        );

        $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertSame($workerRequest, $GLOBALS['TYPO3_REQUEST']);
        self::assertSame($frontendUser, $this->context->getAspect('frontend.user'));
        self::assertSame($visibility, $this->context->getAspect('visibility'));
        self::assertNotSame(0, $this->context->getAspect('date')->get('timestamp'));
    }

    #[Test]
    public function fetchUrlContentsRemovesRequestOfFrontendWithoutWorkerRequest(): void
    {
        unset($GLOBALS['TYPO3_REQUEST'], $GLOBALS['ACCESS_TIME']);
        $this->application->method('handle')->willReturnCallback(
            static function (ServerRequestInterface $request): ResponseInterface {
                $GLOBALS['TYPO3_REQUEST'] = $request;
                return new Response();
            }
        );

        $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertArrayNotHasKey('TYPO3_REQUEST', $GLOBALS);
        self::assertArrayNotHasKey('ACCESS_TIME', $GLOBALS);
    }

    #[Test]
    public function fetchUrlContentsResetsPageRendererBeforeAndAfterRendering(): void
    {
        $calls = [];
        $this->pageRenderer->expects(self::exactly(2))->method('reset')->willReturnCallback(
            static function () use (&$calls): void {
                $calls[] = 'reset';
            }
        );
        $this->application->method('handle')->willReturnCallback(
            static function () use (&$calls): ResponseInterface {
                $calls[] = 'handle';
                return new Response();
            }
        );

        $this->createSubject()->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID);

        self::assertSame(['reset', 'handle', 'reset'], $calls);
    }

    #[Test]
    public function fetchUrlContentsWorksWithoutLogger(): void
    {
        $this->respondWith(new Response('php://temp', 404));
        $subject = $this->createSubject(withLogger: false);

        self::assertFalse($subject->fetchUrlContents(new Uri('not-an-url'), self::CRAWLER_ID));
        self::assertSame(
            [
                'errorlog' => ['404 Not Found'],
            ],
            $subject->fetchUrlContents(new Uri('https://example.com/'), self::CRAWLER_ID)
        );
    }

    private function respondWith(ResponseInterface ...$responses): void
    {
        $this->application->method('handle')->willReturnCallback(
            function (ServerRequestInterface $request) use (&$responses): ResponseInterface {
                $this->handledRequests[] = $request;
                return array_shift($responses) ?? throw new \LogicException('No response left', 1);
            }
        );
    }

    private function createSubject(array $settings = [], bool $withLogger = true): FrontendSubRequestExecutionStrategy
    {
        $configurationProvider = $this->createStub(ExtensionConfigurationProvider::class);
        $configurationProvider->method('getExtensionConfiguration')->willReturn($settings);

        $subject = new FrontendSubRequestExecutionStrategy(
            $this->application,
            $this->context,
            $this->pageRenderer,
            $configurationProvider,
            new FrozenClock(self::NOW),
        );
        if ($withLogger) {
            $subject->setLogger($this->logger);
        }
        return $subject;
    }
}
