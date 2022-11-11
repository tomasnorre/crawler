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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * @covers \AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy
 */
class GuzzleExecutionStrategyTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var GuzzleExecutionStrategy
     */
    protected $guzzleExecutionStrategy;

    protected function setUp(): void
    {
        $this->guzzleExecutionStrategy = $this->createPartialMock(GuzzleExecutionStrategy::class, ['getResponse']);

        $response = $this->createPartialMock(Response::class, ['getHeaderLine']);
        $response->method('getHeaderLine')
            ->willReturn(serialize('Crawler extension for TYPO3'));

        $this->guzzleExecutionStrategy
            ->method('getResponse')
            ->willReturn($response);
    }

    /**
     * @test
     */
    public function fetchUrlContents(): void
    {
        $crawlerId = sha1('this-is-testing');
        $url = new Uri('https://not-important.tld');

        self::assertStringContainsString(
            'Crawler extension for TYPO3',
            $this->guzzleExecutionStrategy->fetchUrlContents($url, $crawlerId)
        );
    }

    /**
     * @test
     */
    public function fetchUrlContentThrowsException(): void
    {
        $message = 'Error while opening "https://not-important.tld" - 0 cURL error 6: Could not resolve host: not-important.tld (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://not-important.tld';
        if ((new Typo3Version())->getMajorVersion() === 11) {
            $message .= ' for https://not-important.tld';
        }

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->debug($message, ['crawlerId' => '2981d019ade833a37995c1b569ef87b6b5af7287'])->shouldBeCalledOnce();

        $crawlerId = sha1('this-is-testing');
        $url = new Uri('https://not-important.tld');
        $guzzleExecutionStrategy = $this->createPartialMock(GuzzleExecutionStrategy::class, []);
        $guzzleExecutionStrategy->setLogger($logger->reveal());

        self::assertStringContainsString(
            'cURL error 6: Could not resolve host: not-important.tld (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)',
            $guzzleExecutionStrategy->fetchUrlContents($url, $crawlerId)
        );
    }
}
