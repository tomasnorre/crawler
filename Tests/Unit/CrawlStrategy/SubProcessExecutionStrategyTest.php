<?php

declare(strict_types=1);

namespace TomasNorre\Crawler\Tests\Unit\CrawlStrategy;

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

use Nimut\TestingFramework\TestCase\UnitTestCase;
use TomasNorre\Crawler\CrawlStrategy\SubProcessExecutionStrategy;

class SubProcessExecutionStrategyTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider buildRequestHandlersDataProvider
     */
    public function buildRequestHeadersReturnsArray(array $url, string $crawlerId, array $expected): void
    {
        self::markTestSkipped('This is skipped as buildRequestHeaders() is now private, I need to change the test to ensure it is tested as part of the fetchUrlContents()');
        $subProcessExecutionStrategy = $this->getAccessibleMock(SubProcessExecutionStrategy::class, ['dummy'], [], '', false);

        self::assertEquals(
            $expected,
            $subProcessExecutionStrategy->_call('buildRequestHeaders', $url, $crawlerId)
        );
    }

    public function buildRequestHandlersDataProvider(): array
    {
        $path = '/path/to/page';
        $query = 'q=keyword';
        $host = 'www.example.com';
        $user = 'username';
        $pass = 'password';
        $crawlerId = 'as23dr32a';

        return [
            'No Username' => [
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
            ],
            'No Password' => [
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
            ],
            'Username and Password' => [
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
            ],
            'Without query' => [
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
            ],
        ];
    }
}
