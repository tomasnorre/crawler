<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Value;

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

use AOE\Crawler\Value\CrawlResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(CrawlResult::class)]
class CrawlResultTest extends UnitTestCase
{
    #[Test]
    public function fromContentKeepsContent(): void
    {
        $content = [
            'vars' => [
                'id' => 5,
            ],
        ];

        self::assertSame($content, CrawlResult::fromContent($content)->getContent());
    }

    #[Test]
    public function errorHasMessageInErrorLog(): void
    {
        self::assertSame([
            'errorlog' => ['404 Not Found'],
        ], CrawlResult::error('404 Not Found')->getContent());
    }

    #[Test]
    public function errorWithVarsHasVars(): void
    {
        self::assertSame([
            'errorlog' => ['Response has no X-T3Crawler-Meta header'],
            'vars' => [
                'status' => '200 OK',
            ],
        ], CrawlResult::error('Response has no X-T3Crawler-Meta header', [
            'status' => '200 OK',
        ])->getContent());
    }

    #[Test]
    public function toResultDataEncodesContentTwice(): void
    {
        self::assertSame(
            '{"content":"{\"errorlog\":[\"Skipped\"]}"}',
            CrawlResult::error('Skipped')->toResultData()
        );
    }

    #[Test]
    public function toResultDataEncodesScalarContent(): void
    {
        self::assertSame('{"content":"\"Hi\""}', CrawlResult::fromContent('Hi')->toResultData());
    }

    #[Test]
    public function toResultDataEncodesContentThatIsNotJsonEncodableAsFalse(): void
    {
        self::assertSame('{"content":false}', CrawlResult::fromContent(NAN)->toResultData());
    }

    public static function notCrawledResultDataProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'invalid JSON' => ['{no json'];
        yield 'false' => ['false'];
        yield 'null' => ['null'];
        yield 'empty array' => ['[]'];
        yield 'empty object' => ['{}'];
    }

    #[DataProvider('notCrawledResultDataProvider')]
    #[Test]
    public function fromResultDataReturnsNullIfNotCrawled(string $resultData): void
    {
        self::assertNull(CrawlResult::fromResultData($resultData));
    }

    public static function resultDataProvider(): iterable
    {
        yield 'crawler data' => [
            'resultData' => '{"content":"{\"vars\":{\"id\":5}}"}',
            'expected' => [
                'vars' => [
                    'id' => 5,
                ],
            ],
        ];
        yield 'scalar return value of a callback' => [
            'resultData' => '{"content":"\"Hi\""}',
            'expected' => 'Hi',
        ];
        yield 'integer content' => [
            'resultData' => '{"content":1}',
            'expected' => 1,
        ];
        yield 'content is no JSON' => [
            'resultData' => '{"content":"<html>"}',
            'expected' => null,
        ];
        yield 'content is not JSON encodable' => [
            'resultData' => '{"content":false}',
            'expected' => null,
        ];
        yield 'content is an array' => [
            'resultData' => '{"content":["a"]}',
            'expected' => null,
        ];
        yield 'no content' => [
            'resultData' => '{"other":"value"}',
            'expected' => null,
        ];
        yield 'string of an old queue entry with invalid parameters' => [
            'resultData' => '"ERROR"',
            'expected' => null,
        ];
    }

    #[DataProvider('resultDataProvider')]
    #[Test]
    public function fromResultDataDecodesContent(string $resultData, mixed $expected): void
    {
        self::assertSame($expected, CrawlResult::fromResultData($resultData)?->getContent());
    }

    #[Test]
    public function fromResultDataReadsResultOfToResultData(): void
    {
        $result = CrawlResult::fromContent([
            'log' => ['User Groups: 1'],
            'vars' => [
                'id' => 5,
            ],
        ]);

        self::assertEquals($result, CrawlResult::fromResultData($result->toResultData()));
    }

    public static function crawlerDataProvider(): iterable
    {
        yield 'array' => [[], true];
        yield 'string' => ['Hi', false];
        yield 'null' => [null, false];
    }

    #[DataProvider('crawlerDataProvider')]
    #[Test]
    public function hasCrawlerDataIfContentIsArray(mixed $content, bool $expected): void
    {
        self::assertSame($expected, CrawlResult::fromContent($content)->hasCrawlerData());
    }

    public static function stringListDataProvider(): iterable
    {
        yield 'no crawler data' => [
            'content' => 'Hi',
            'expected' => [],
        ];
        yield 'missing' => [
            'content' => [],
            'expected' => [],
        ];
        yield 'no array' => [
            'content' => [
                'errorlog' => 'error',
                'log' => 'line',
            ],
            'expected' => [],
        ];
        yield 'list of strings' => [
            'content' => [
                'errorlog' => ['first', 'second'],
                'log' => ['first', 'second'],
            ],
            'expected' => ['first', 'second'],
        ];
        yield 'keys are dropped' => [
            'content' => [
                'errorlog' => [
                    'a' => 'first',
                    'b' => 'second',
                ],
                'log' => [
                    'a' => 'first',
                    'b' => 'second',
                ],
            ],
            'expected' => ['first', 'second'],
        ];
        yield 'scalars are converted, other values dropped' => [
            'content' => [
                'errorlog' => [1, ['nested'], true, null, 'last'],
                'log' => [1, ['nested'], true, null, 'last'],
            ],
            'expected' => ['1', '1', 'last'],
        ];
    }

    #[DataProvider('stringListDataProvider')]
    #[Test]
    public function getErrorLogReturnsStrings(mixed $content, array $expected): void
    {
        self::assertSame($expected, CrawlResult::fromContent($content)->getErrorLog());
    }

    #[DataProvider('stringListDataProvider')]
    #[Test]
    public function getLogReturnsStrings(mixed $content, array $expected): void
    {
        self::assertSame($expected, CrawlResult::fromContent($content)->getLog());
    }

    public static function varsDataProvider(): iterable
    {
        yield 'no crawler data' => [
            'content' => 'Hi',
            'expected' => [],
        ];
        yield 'missing' => [
            'content' => [],
            'expected' => [],
        ];
        yield 'no array' => [
            'content' => [
                'vars' => 'id',
            ],
            'expected' => [],
        ];
        yield 'vars' => [
            'content' => [
                'vars' => [
                    'id' => 5,
                    'gr_list' => '0,-1',
                ],
            ],
            'expected' => [
                'id' => 5,
                'gr_list' => '0,-1',
            ],
        ];
    }

    #[DataProvider('varsDataProvider')]
    #[Test]
    public function getVarsReturnsVarsOfCrawlerData(mixed $content, array $expected): void
    {
        self::assertSame($expected, CrawlResult::fromContent($content)->getVars());
    }

    public static function statusDataProvider(): iterable
    {
        yield 'no error log' => [
            'content' => [],
            'expected' => 'OK',
        ];
        yield 'empty error log' => [
            'content' => [
                'errorlog' => [],
            ],
            'expected' => 'OK',
        ];
        yield 'one error' => [
            'content' => [
                'errorlog' => ['500 Internal Server Error'],
            ],
            'expected' => '500 Internal Server Error',
        ];
        yield 'two errors' => [
            'content' => [
                'errorlog' => ['500 Internal Server Error', '503 Service Unavailable'],
            ],
            'expected' => '500 Internal Server Error' . chr(10) . '503 Service Unavailable',
        ];
        yield 'no crawler data' => [
            'content' => null,
            'expected' => 'Error - no info, sorry!',
        ];
        yield 'scalar return value of a callback' => [
            'content' => 'Hi',
            'expected' => 'Error - no info, sorry!',
        ];
    }

    #[DataProvider('statusDataProvider')]
    #[Test]
    public function getStatusReturnsOkOrErrors(mixed $content, string $expected): void
    {
        self::assertSame($expected, CrawlResult::fromContent($content)->getStatus());
    }
}
