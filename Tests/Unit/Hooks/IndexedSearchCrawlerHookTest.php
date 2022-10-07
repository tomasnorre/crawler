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
    protected \Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface|\PHPUnit\Framework\MockObject\MockObject $subject;

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

    public function checkUrlDataProvider(): iterable
    {
        yield 'Url with // at the end of Url' => [
            'url' => 'example.com/page-one//',
            'urlLog' => [],
            'baseUrl' => 'example.com',
            'expected' => 'example.com/page-one/',
        ];
        yield 'Url with // and # in url' => [
            'url' => 'example.com/page-one//#marker',
            'urlLog' => [],
            'baseUrl' => 'example.com',
            'expected' => 'example.com/page-one//',
        ];
        yield 'Url without // at the end' => [
            'url' => 'example.com/page-one',
            'urlLog' => [],
            'baseUrl' => 'example.com',
            'expected' => 'example.com/page-one',
        ];
        yield 'Url without // but with #' => [
            'url' => 'example.com/page-one#marker',
            'urlLog' => [],
            'baseUrl' => 'example.com',
            'expected' => 'example.com/page-one',
        ];
        yield 'Url with ../' => [
            'url' => '/../fileadmin/images.png',
            'urlLog' => [],
            'baseUrl' => '',
            'expected' => false,
        ];
        yield 'url as part of baseUrl' => [
            'url' => 'example.com/page-one',
            'urlLog' => [],
            'baseUrl' => 'example.com',
            'expected' => 'example.com/page-one',
        ];
        yield 'Url not part of baseUrl' => [
            'url' => 'example.com',
            'urlLog' => [],
            'baseUrl' => 'example.tld',
            'expected' => false,
        ];
        yield 'Url in UrlLog' => [
            'url' => 'example.com/page-one',
            'urlLog' => ['example.com/page-one'],
            'baseUrl' => '',
            'expected' => false,
        ];
        yield 'Url not in UrlLog' => [
            'url' => 'example.com/page-one',
            'urlLog' => ['example.com/page-two'],
            'baseUrl' => 'example.com',
            'expected' => 'example.com/page-one',
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
        $GLOBALS['EXEC_TIME'] = 1_591_637_103;
        date_default_timezone_set('UTC');

        self::assertSame(
            $expected,
            $this->subject->generateNextIndexingTime($configurationRecord)
        );
    }

    public function generateNextIndexingTimeDataProvider(): iterable
    {
        yield 'Timer Frequency less than 24 hours (5 hours)' => [
            'configurationRecord' => [
                'timer_frequency' => 18000,
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => 60,
            ],
            'atMidnightTimestamp' => 1_593_475_200,
            'expected' => 1_591_639_260,
        ];
        yield 'Timer frequency more than 24 hours (26 hours)' => [
            'configurationRecord' => [
                'timer_frequency' => 93600,
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => 60,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_668_060,
        ];
        yield 'Offset at minimum in range' => [
            'configurationRecord' => [
                'timer_frequency' => 500,
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => 0,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_637_400,
        ];
        yield 'Offset at maximum in range' => [
            'configurationRecord' => [
                'timer_frequency' => 500,
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => 86400,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_637_300,
        ];
        yield 'Offset at smaller than rangen' => [
            'configurationRecord' => [
                'timer_frequency' => 500,
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => -1,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_637_400,
        ];
        yield 'Offset larger than range' => [
            'configurationRecord' => [
                'timer_frequency' => 500,
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => 86401,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_637_300,
        ];
        yield 'Timer frequency as numeric string' => [
            'configurationRecord' => [
                'timer_frequency' => '500',
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => 60,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_637_460,
        ];
        yield 'Timer frequency as numeric value' => [
            'configurationRecord' => [
                'timer_frequency' => 500,
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => 60,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_637_460,
        ];
        yield 'Timer next indexing set to positive' => [
            'configurationRecord' => [
                'timer_frequency' => 500,
                'timer_next_indexing' => 1_591_638_558,
                'timer_offset' => 60,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_637_460,
        ];
        yield 'Timer next indexing set to negative' => [
            'configurationRecord' => [
                'timer_frequency' => 500,
                'timer_next_indexing' => -1,
                'timer_offset' => 60,
            ],
            'atMidnightTimestamp' => 1_591_574_400,
            'expected' => 1_591_637_460,
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

    public function checkDeniedSubUrlsDataProvider(): iterable
    {
        yield 'Url not part of url_deny' => [
            'url' => 'example.com',
            'url_deny' => 'example.tld',
            'expected' => false,
        ];
        yield 'Url part of url_deny (1 element in list)' => [
            'url' => 'example.com',
            'url_deny' => 'example.com',
            'expected' => true,
        ];
        yield 'Url part of url_deny (2 elements in list)' => [
            'url' => 'example.com',
            'url_deny' => 'example.tld' . chr(10) . 'example.com',
            'expected' => true,
        ];
        yield 'Url part of url_deny one to one the same' => [
            'url' => 'example.com/page-one/',
            'url_deny' => 'example.com/page-one/',
            'expected' => true,
        ];
        yield 'Url part of url_deny only domain the same' => [
            'url' => 'example.com/page-one/',
            'url_deny' => 'example.com',
            'expected' => true,
        ];
        yield 'url_deny that needs trimming left' => [
            'url' => 'example.com/page-one/',
            'url_deny' => chr(10) . 'example.com',
            'expected' => true,
        ];
        yield 'url_deny that needs trimming right' => [
            'url' => 'example.com/page-one/',
            'url_deny' => 'example.com' . chr(10),
            'expected' => true,
        ];
        yield 'url_deny that needs trimming left and right' => [
            'url' => 'example.com/page-one/',
            'url_deny' => chr(10) . 'example.com' . chr(10),
            'expected' => true,
        ];
        yield 'url_deny that needs trimming - multiple whitespace' => [
            'url' => 'example.com/page-one/',
            'url_deny' => chr(10) . chr(10) . 'example.com' . chr(10) . chr(10) . chr(10),
            'expected' => true,
        ];
        yield 'url_deny with more domains, denied' => [
            'url' => 'example.com/page-one/',
            'url_deny' => ' example.tld ' . chr(10) . chr(10) . ' example.com     ',
            'expected' => true,
        ];
        yield 'url_deny with more domains, not denied' => [
            'url' => 'example.com/page-one/',
            'url_deny' => ' example.tld' . chr(10) . ' example-site.com     ',
            'expected' => false,
        ];
    }
}
