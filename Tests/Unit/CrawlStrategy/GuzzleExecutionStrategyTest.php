<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Tests\Unit\CrawlStrategy;

use AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;

/**
 * @covers \AOE\Crawler\CrawlStrategy\GuzzleExecutionStrategy
 */
class GuzzleExecutionStrategyTest extends UnitTestCase
{
    /**
     * @var GuzzleExecutionStrategy
     */
    protected $guzzleExecutionStrategy;

    protected function setUp(): void
    {
        $this->guzzleExecutionStrategy = $this->createPartialMock(
            GuzzleExecutionStrategy::class,
            ['getResponse']
        );

        $body = $this->createPartialMock(Stream::class, ['getContents']);
        $body->method('getContents')
            ->willReturn(serialize('Crawler extension for TYPO3'));

        $response = $this->createPartialMock(Response::class, ['getBody']);
        $response->method('getBody')
            ->willReturn($body);

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
}
