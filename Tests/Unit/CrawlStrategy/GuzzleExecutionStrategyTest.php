<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
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

use AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy::class)]
class GuzzleExecutionStrategyTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @var GuzzleExecutionStrategy
     */
    protected $guzzleExecutionStrategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guzzleExecutionStrategy = $this->createPartialMock(GuzzleExecutionStrategy::class, ['getResponse']);

        $response = $this->createPartialMock(Response::class, ['getHeaderLine', 'getStatusCode', 'hasHeader']);
        $response->method('getStatusCode')
            ->willReturn(200);
        $response->method('getHeaderLine')
            ->willReturn(json_encode('Crawler extension for TYPO3'));
        $response->method('hasHeader')
            ->willReturn(true);

        $this->guzzleExecutionStrategy
            ->method('getResponse')
            ->willReturn($response);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContents(): void
    {
        $crawlerId = sha1('this-is-testing');
        $url = new Uri('https://not-important.tld');

        self::assertStringContainsString(
            'Crawler extension for TYPO3',
            $this->guzzleExecutionStrategy->fetchUrlContents($url, $crawlerId)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsNoHeader(): void
    {
        $this->guzzleExecutionStrategy = $this->createPartialMock(GuzzleExecutionStrategy::class, ['getResponse']);

        $response = $this->createPartialMock(Response::class, ['getHeaderLine', 'getStatusCode', 'hasHeader']);
        $response->method('getStatusCode')
            ->willReturn(200);
        $response->method('hasHeader')
            ->willReturn(false);

        $this->guzzleExecutionStrategy
            ->method('getResponse')
            ->willReturn($response);

        $crawlerId = sha1('this-is-testing');
        $url = new Uri('https://not-important.tld');

        $response = $this->guzzleExecutionStrategy->fetchUrlContents($url, $crawlerId);
        $this->assertEquals(
            [
                'errorlog' => ['Response has no X-T3Crawler-Meta header'],
                'vars' => [
                    'status' => '200 ',
                ],
            ],
            $response
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentThrowsException(): void
    {
        $errorMessage = 'cURL error 6: Could not resolve host: not-important.tld (see https://curl.se/libcurl/c/libcurl-errors.html) for https://not-important.tld';
        $crawlerId = sha1('this-is-testing');

        // Replaces only the RequestFactory instead of getResponse(), so getResponse() is covered without network access.
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::once())->method('request')->with('https://not-important.tld', 'GET', [
            'headers' => [
                'Connection' => 'close',
                'X-T3Crawler' => $crawlerId,
                'User-Agent' => 'TYPO3 crawler',
            ],
            'connect_timeout' => 5.0,
        ])->willThrowException(
            new ConnectException($errorMessage, new Request('https://not-important.tld', 'GET'))
        );
        GeneralUtility::addInstance(RequestFactory::class, $requestFactory);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(
            'Error while opening "https://not-important.tld" - 0 ' . $errorMessage,
            [
                'crawlerId' => '2981d019ade833a37995c1b569ef87b6b5af7287',
            ]
        );

        $guzzleExecutionStrategy = $this->createPartialMock(GuzzleExecutionStrategy::class, []);
        $guzzleExecutionStrategy->setLogger($logger);

        self::assertSame([
            'errorlog' => ['0 ' . $errorMessage],
        ], $guzzleExecutionStrategy->fetchUrlContents(new Uri('https://not-important.tld'), $crawlerId));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsRequestsUrlWithCrawlerHeaders(): void
    {
        $subject = $this->createPartialMock(GuzzleExecutionStrategy::class, ['getResponse']);
        $subject->expects(self::once())->method('getResponse')->with('https://example.com/page', [
            'headers' => [
                'Connection' => 'close',
                'X-T3Crawler' => 'crawler-id',
                'User-Agent' => 'TYPO3 crawler',
            ],
            'connect_timeout' => 5.0,
        ])->willReturn(new Response('php://temp', 200, [
            'X-T3Crawler-Meta' => '{"vars":{"status":"200 OK"}}',
        ]));

        self::assertSame([
            'vars' => [
                'status' => '200 OK',
            ],
        ], $subject->fetchUrlContents(new Uri('https://example.com/page'), 'crawler-id'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsAuthenticatesWithUserInfoOfUrl(): void
    {
        $subject = $this->createPartialMock(GuzzleExecutionStrategy::class, ['getResponse']);
        $subject->expects(self::once())->method('getResponse')->with(
            'https://user:secret@example.com/',
            self::callback(static function (array $options): bool {
                self::assertSame(['user', 'secret'], $options['auth']);
                return true;
            })
        )->willReturn(new Response('php://temp', 404, [], 'Not Found'));

        self::assertSame([
            'errorlog' => ['Response has no X-T3Crawler-Meta header'],
            'vars' => [
                'status' => '404 Not Found',
            ],
        ], $subject->fetchUrlContents(new Uri('https://user:secret@example.com/'), 'crawler-id'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsReturnsStatusOfErrorResponse(): void
    {
        $exception = new RequestException(
            'Server error',
            new Request('https://example.com/', 'GET'),
            new Response('php://temp', 503, [], 'Service Unavailable')
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(
            'Error while opening "https://example.com/" - 503 Service Unavailable',
            [
                'crawlerId' => 'crawler-id',
            ]
        );
        $subject = $this->createPartialMock(GuzzleExecutionStrategy::class, ['getResponse']);
        $subject->method('getResponse')->willThrowException($exception);
        $subject->setLogger($logger);

        self::assertSame([
            'errorlog' => ['503 Service Unavailable'],
        ], $subject->fetchUrlContents(new Uri('https://example.com/'), 'crawler-id'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsReturnsMessageOfRequestExceptionWithoutResponse(): void
    {
        $subject = $this->createPartialMock(GuzzleExecutionStrategy::class, ['getResponse']);
        $subject->method('getResponse')->willThrowException(
            new RequestException('Too many redirects', new Request('https://example.com/', 'GET'))
        );
        $subject->setLogger($this->createStub(LoggerInterface::class));

        self::assertSame([
            'errorlog' => ['0 Too many redirects'],
        ], $subject->fetchUrlContents(new Uri('https://example.com/'), 'crawler-id'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchUrlContentsReturnsCodeAndMessageOfConnectException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(
            'Error while opening "https://example.com/" - 0 Connection refused',
            [
                'crawlerId' => 'crawler-id',
            ]
        );
        $subject = $this->createPartialMock(GuzzleExecutionStrategy::class, ['getResponse']);
        $subject->method('getResponse')->willThrowException(
            new ConnectException('Connection refused', new Request('https://example.com/', 'GET'))
        );
        $subject->setLogger($logger);

        self::assertSame([
            'errorlog' => ['0 Connection refused'],
        ], $subject->fetchUrlContents(new Uri('https://example.com/'), 'crawler-id'));
    }
}
