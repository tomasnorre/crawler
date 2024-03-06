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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @covers \AOE\Crawler\CrawlStrategy\SubProcessExecutionStrategy
 */
class SubProcessExecutionStrategyTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function constructorTest(): void
    {
        $configuration = [
            'makeDirectRequests' => 0,
            'frontendBasePath' => '/',
        ];

        $extensionConfigurationProvider = $this->prophesize(ExtensionConfigurationProvider::class);
        $extensionConfigurationProvider->getExtensionConfiguration()->willReturn($configuration);
        $crawlStrategy = GeneralUtility::makeInstance(
            SubProcessExecutionStrategy::class,
            $extensionConfigurationProvider->reveal()
        );

        self::assertInstanceOf(SubProcessExecutionStrategy::class, $crawlStrategy);
    }

    /**
     * @test
     */
    public function fetchUrlContentsInvalidSchema(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->debug(
            'Scheme does not match for url "/not-an-url"',
            ['crawlerId' => '2981d019ade833a37995c1b569ef87b6b5af7287']
        )->shouldBeCalledOnce();

        $crawlerId = sha1('this-is-testing');
        $url = new Uri('not-an-url');
        $subProcessExecutionStrategy = $this->createPartialMock(SubProcessExecutionStrategy::class, []);
        $subProcessExecutionStrategy->setLogger($logger->reveal());

        self::assertFalse($subProcessExecutionStrategy->fetchUrlContents($url, $crawlerId));
    }

    /**
     * @test
     * @dataProvider buildRequestHandlersDataProvider
     */
    public function buildRequestHeadersReturnsArray(array $url, string $crawlerId, array $expected): never
    {
        self::markTestSkipped(
            'This is skipped as buildRequestHeaders() is now private, I need to change the test to ensure it is tested as part of the fetchUrlContents()'
        );
        $subProcessExecutionStrategy = $this->getAccessibleMock(SubProcessExecutionStrategy::class, [], [], '', false);

        self::assertEquals(
            $expected,
            $subProcessExecutionStrategy->_call('buildRequestHeaders', $url, $crawlerId)
        );
    }

    public function buildRequestHandlersDataProvider(): iterable
    {
        $path = '/path/to/page';
        $query = 'q=keyword';
        $host = 'www.example.com';
        $user = 'username';
        $pass = 'password';
        $crawlerId = 'as23dr32a';

        yield 'No Username' => [
            'url' => [
                'path' => $path,
                'query' => $query,
                'host' => $host,
                'pass' => $pass,
            ],
            'crawlerId' => $crawlerId,
            'expected' => [
                'GET ' . $path . '?' . $query . ' HTTP/1.0',
                'Host: ' . $host,
                'Connection: close',
                'X-T3crawler: ' . $crawlerId,
                'User-Agent: TYPO3 crawler',
            ],
        ];
        yield 'No Password' => [
            'url' => [
                'path' => $path,
                'query' => $query,
                'host' => $host,
                'user' => $user,
            ],
            'crawlerId' => $crawlerId,
            'expected' => [
                'GET ' . $path . '?' . $query . ' HTTP/1.0',
                'Host: ' . $host,
                'Connection: close',
                'X-T3crawler: ' . $crawlerId,
                'User-Agent: TYPO3 crawler',
            ],
        ];
        yield 'Username and Password' => [
            'url' => [
                'path' => $path,
                'query' => $query,
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
            ],
            'crawlerId' => $crawlerId,
            'expected' => [
                'GET ' . $path . '?' . $query . ' HTTP/1.0',
                'Host: ' . $host,
                'Connection: close',
                'Authorization: Basic ' . base64_encode($user . ':' . $pass),
                'X-T3crawler: ' . $crawlerId,
                'User-Agent: TYPO3 crawler',
            ],
        ];
        yield 'Without query' => [
            'url' => [
                'path' => $path,
                'host' => $host,
            ],
            'crawlerId' => $crawlerId,
            'expected' => [
                'GET ' . $path . ' HTTP/1.0',
                'Host: ' . $host,
                'Connection: close',
                'X-T3crawler: ' . $crawlerId,
                'User-Agent: TYPO3 crawler',
            ],
        ];
    }
}
