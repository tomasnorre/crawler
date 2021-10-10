<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Hooks;

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

use AOE\Crawler\Hooks\IndexedSearchCrawlerHook;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * @covers \AOE\Crawler\Hooks\IndexedSearchCrawlerFilesHook
 */
class IndexedSearchCrawlerHookTest extends UnitTestCase
{
    /**
     * @var IndexedSearchCrawlerHook
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = self::getAccessibleMock(IndexedSearchCrawlerHook::class, ['getMidnightTimestamp'], [], '', false);
    }

    /**
     * @param $expected
     *
     * @test
     * @dataProvider checkUrlDataProvider
     */
    public function checkUrl(string $url, array $urlLog, string $baseUrl, $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->checkUrl($url, $urlLog, $baseUrl)
        );
    }

    public function checkUrlDataProvider(): array
    {
        return [
            'Url with // at the end of Url' => [
                'url' => 'example.com/page-one//',
                'urlLog' => [],
                'baseUrl' => 'example.com',
                'expected' => 'example.com/page-one/',
            ],
            'Url with // and # in url' => [
                'url' => 'example.com/page-one//#marker',
                'urlLog' => [],
                'baseUrl' => 'example.com',
                'expected' => 'example.com/page-one//',
            ],
            'Url without // at the end' => [
                'url' => 'example.com/page-one',
                'urlLog' => [],
                'baseUrl' => 'example.com',
                'expected' => 'example.com/page-one',
            ],
            'Url without // but with #' => [
                'url' => 'example.com/page-one#marker',
                'urlLog' => [],
                'baseUrl' => 'example.com',
                'expected' => 'example.com/page-one',
            ],
            'Url with ../' => [
                'url' => '/../fileadmin/images.png',
                'urlLog' => [],
                'baseUrl' => '',
                'expected' => false,
            ],
            'url as part of baseUrl' => [
                'url' => 'example.com/page-one',
                'urlLog' => [],
                'baseUrl' => 'example.com',
                'expected' => 'example.com/page-one',
            ],
            'Url not part of baseUrl' => [
                'url' => 'example.com',
                'urlLog' => [],
                'baseUrl' => 'example.tld',
                'expected' => false,
            ],
            'Url in UrlLog' => [
                'url' => 'example.com/page-one',
                'urlLog' => ['example.com/page-one'],
                'baseUrl' => '',
                'expected' => false,
            ],
            'Url not in UrlLog' => [
                'url' => 'example.com/page-one',
                'urlLog' => ['example.com/page-two'],
                'baseUrl' => 'example.com',
                'expected' => 'example.com/page-one',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider generateNextIndexingTimeDataProvider
     */
    public function generateNextIndexingTime(array $configurationRecord, int $atMidnightTimestamp, int $expected): void
    {
        $this->subject->method('getMidnightTimestamp')->willReturn($atMidnightTimestamp);

        // Force "current time"
        $GLOBALS['EXEC_TIME'] = 1591637103;
        date_default_timezone_set('UTC');

        self::assertSame(
            $expected,
            $this->subject->generateNextIndexingTime($configurationRecord)
        );
    }

    public function generateNextIndexingTimeDataProvider(): array
    {
        return [
            'Timer Frequency less than 24 hours (5 hours)' => [
                'configurationRecord' => [
                    'timer_frequency' => 18000,
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => 60,
                ],
                'atMidnightTimestamp' => 1593475200,
                'expected' => 1591639260,
            ],
            'Timer frequency more than 24 hours (26 hours)' => [
                'configurationRecord' => [
                    'timer_frequency' => 93600,
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => 60,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591668060,
            ],
            'Offset at minimum in range' => [
                'configurationRecord' => [
                    'timer_frequency' => 500,
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => 0,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591637400,
            ],
            'Offset at maximum in range' => [
                'configurationRecord' => [
                    'timer_frequency' => 500,
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => 86400,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591637300,
            ],
            'Offset at smaller than rangen' => [
                'configurationRecord' => [
                    'timer_frequency' => 500,
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => -1,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591637400,
            ],
            'Offset larger than range' => [
                'configurationRecord' => [
                    'timer_frequency' => 500,
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => 86401,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591637300,
            ],
            'Timer frequency as numeric string' => [
                'configurationRecord' => [
                    'timer_frequency' => '500',
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => 60,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591637460,
            ],
            'Timer frequency as numeric value' => [
                'configurationRecord' => [
                    'timer_frequency' => 500,
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => 60,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591637460,
            ],
            'Timer next indexing set to positive' => [
                'configurationRecord' => [
                    'timer_frequency' => 500,
                    'timer_next_indexing' => 1591638558,
                    'timer_offset' => 60,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591637460,
            ],
            'Timer next indexing set to negative' => [
                'configurationRecord' => [
                    'timer_frequency' => 500,
                    'timer_next_indexing' => -1,
                    'timer_offset' => 60,
                ],
                'atMidnightTimestamp' => 1591574400,
                'expected' => 1591637460,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider checkDeniedSubUrlsDataProvider
     */
    public function checkDeniedSubUrls(string $url, string $url_deny, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->checkDeniedSuburls($url, $url_deny)
        );
    }

    public function checkDeniedSubUrlsDataProvider(): array
    {
        return [
            'Url not part of url_deny' => [
                'url' => 'example.com',
                'url_deny' => 'example.tld',
                'expected' => false,
            ],
            'Url part of url_deny (1 element in list)' => [
                'url' => 'example.com',
                'url_deny' => 'example.com',
                'expected' => true,
            ],
            'Url part of url_deny (2 elements in list)' => [
                'url' => 'example.com',
                'url_deny' => 'example.tld' . chr(10) . 'example.com',
                'expected' => true,
            ],
            'Url part of url_deny one to one the same' => [
                'url' => 'example.com/page-one/',
                'url_deny' => 'example.com/page-one/',
                'expected' => true,
            ],
            'Url part of url_deny only domain the same' => [
                'url' => 'example.com/page-one/',
                'url_deny' => 'example.com',
                'expected' => true,
            ],
            'url_deny that needs trimming left' => [
                'url' => 'example.com/page-one/',
                'url_deny' => chr(10) . 'example.com',
                'expected' => true,
            ],
            'url_deny that needs trimming right' => [
                'url' => 'example.com/page-one/',
                'url_deny' => 'example.com' . chr(10),
                'expected' => true,
            ],
            'url_deny that needs trimming left and right' => [
                'url' => 'example.com/page-one/',
                'url_deny' => chr(10) . 'example.com' . chr(10),
                'expected' => true,
            ],
            'url_deny that needs trimming - multiple whitespace' => [
                'url' => 'example.com/page-one/',
                'url_deny' => chr(10) . chr(10) . 'example.com' . chr(10) . chr(10) . chr(10),
                'expected' => true,
            ],
            'url_deny with more domains, denied' => [
                'url' => 'example.com/page-one/',
                'url_deny' => ' example.tld ' . chr(10) . chr(10) . ' example.com     ',
                'expected' => true,
            ],
            'url_deny with more domains, not denied' => [
                'url' => 'example.com/page-one/',
                'url_deny' => ' example.tld' . chr(10) . ' example-site.com     ',
                'expected' => false,
            ],
        ];
    }
}
