<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Controller\Backend;

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

use AOE\Crawler\Controller\Backend\BackendModuleCrawlerLogController;
use AOE\Crawler\Queue\CrawlPriority;
use AOE\Crawler\Queue\CrawlUrlMessageDispatcher;
use AOE\Crawler\Queue\Message\CrawlUrlMessage;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(BackendModuleCrawlerLogController::class)]
class BackendModuleCrawlerLogControllerTest extends UnitTestCase
{
    /**
     * The set id is handed on to the links of the log filters, so changing a filter keeps the selected set.
     */
    #[DataProvider('setIdDataProvider')]
    #[Test]
    public function readsTheSetIdFromTheRequest(ServerRequest $request, int $expectedSetId): void
    {
        $subject = (new \ReflectionClass(BackendModuleCrawlerLogController::class))->newInstanceWithoutConstructor();

        (new \ReflectionMethod($subject, 'setPropertiesBasedOnPostVars'))->invoke($subject, $request);

        self::assertSame($expectedSetId, (new \ReflectionProperty($subject, 'setId'))->getValue($subject));
    }

    public static function setIdDataProvider(): iterable
    {
        yield 'Set id in the query parameters' => [
            'request' => (new ServerRequest())->withQueryParams([
                'id' => 1,
                'setID' => 123,
            ]),
            'expectedSetId' => 123,
        ];
        yield 'Set id in the parsed body' => [
            'request' => (new ServerRequest())->withQueryParams([
                'id' => 1,
            ])->withParsedBody([
                'setID' => 456,
            ]),
            'expectedSetId' => 456,
        ];
        yield 'No set id' => [
            'request' => (new ServerRequest())->withQueryParams([
                'id' => 1,
            ]),
            'expectedSetId' => 0,
        ];
    }

    #[DataProvider('requestPropertiesDataProvider')]
    #[Test]
    public function readsThePropertiesFromTheRequest(ServerRequest $request, array $expectedProperties): void
    {
        $subject = (new \ReflectionClass(BackendModuleCrawlerLogController::class))->newInstanceWithoutConstructor();

        (new \ReflectionMethod($subject, 'setPropertiesBasedOnPostVars'))->invoke($subject, $request);

        $properties = [];
        foreach (array_keys($expectedProperties) as $propertyName) {
            $properties[$propertyName] = (new \ReflectionProperty($subject, $propertyName))->getValue($subject);
        }
        self::assertSame($expectedProperties, $properties);
    }

    public static function requestPropertiesDataProvider(): iterable
    {
        yield 'Defaults' => [
            'request' => new ServerRequest(),
            'expectedProperties' => [
                'pageUid' => -1,
                'quiPath' => '',
                'queueId' => null,
                'logDisplay' => 'all',
                'itemsPerPage' => 10,
                'showResultLog' => '0',
                'showFeVars' => '0',
                'logDepth' => '0',
            ],
        ];
        yield 'Values in the query parameters' => [
            'request' => (new ServerRequest())->withQueryParams([
                'id' => '5',
                'qid_details' => '12abc',
                'displayLog' => 'finished',
                'itemsPerPage' => '50',
                'ShowResultLog' => '1',
                'ShowFeVars' => '1',
                'logDepth' => '2',
            ]),
            'expectedProperties' => [
                'pageUid' => 5,
                'quiPath' => '&qid_details=12',
                'queueId' => '12abc',
                'logDisplay' => 'finished',
                'itemsPerPage' => 50,
                'showResultLog' => '1',
                'showFeVars' => '1',
                'logDepth' => '2',
            ],
        ];
        yield 'Queue id in the parsed body wins over the query parameters' => [
            'request' => (new ServerRequest())->withQueryParams([
                'qid_details' => '12',
            ])->withParsedBody([
                'qid_details' => '13',
            ]),
            'expectedProperties' => [
                'quiPath' => '&qid_details=13',
                'queueId' => '13',
            ],
        ];
        yield 'Parsed body is an object' => [
            'request' => (new ServerRequest())->withQueryParams([
                'qid_details' => '12',
            ])->withParsedBody(new \stdClass()),
            'expectedProperties' => [
                'quiPath' => '&qid_details=12',
                'queueId' => '12',
            ],
        ];
        yield 'No queue id' => [
            'request' => (new ServerRequest())->withQueryParams([
                'qid_details' => '0',
            ]),
            'expectedProperties' => [
                'quiPath' => '',
                'queueId' => '0',
            ],
        ];
    }

    /**
     * Flushing the queue keeps the log display, unless all entries are flushed. The "Flush" button wins.
     */
    #[DataProvider('flushDataProvider')]
    #[Test]
    public function isFlushRequestedChecksTheFlushButtons(
        array $parsedBody,
        bool $expectedFlush,
        string $expectedLogDisplay
    ): void {
        $subject = (new \ReflectionClass(BackendModuleCrawlerLogController::class))->newInstanceWithoutConstructor();
        $logDisplay = new \ReflectionProperty($subject, 'logDisplay');
        $logDisplay->setValue($subject, 'pending');

        self::assertSame(
            $expectedFlush,
            (new \ReflectionMethod($subject, 'isFlushRequested'))->invoke($subject, $parsedBody)
        );
        self::assertSame($expectedLogDisplay, $logDisplay->getValue($subject));
    }

    public static function flushDataProvider(): iterable
    {
        yield 'No flush' => [[], false, 'pending'];
        yield 'Flush button not pressed' => [[
            '_flush' => '0',
            '_flush_all' => '',
        ], false, 'pending'];
        yield 'Flush' => [[
            '_flush' => '1',
        ], true, 'pending'];
        yield 'Flush all' => [[
            '_flush_all' => '1',
        ], true, 'all'];
        yield 'Flush and flush all' => [[
            '_flush' => '1',
            '_flush_all' => '1',
        ], true, 'pending'];
    }

    #[Test]
    public function dispatchRecrawlRequestDispatchesTheEntryWithHighPriority(): void
    {
        $dispatcher = $this->createMock(CrawlUrlMessageDispatcher::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new CrawlUrlMessage(42, true), 0, 0, CrawlPriority::High);
        $subject = $this->createSubjectWithDispatcher($dispatcher);

        (new \ReflectionMethod($subject, 'dispatchRecrawlRequest'))->invoke(
            $subject,
            (new ServerRequest())->withQueryParams([
                'qid_read' => 42,
            ])
        );
    }

    #[Test]
    public function dispatchRecrawlRequestDoesNothingWithoutQueueId(): void
    {
        $dispatcher = $this->createMock(CrawlUrlMessageDispatcher::class);
        $dispatcher->expects(self::never())->method('dispatch');
        $subject = $this->createSubjectWithDispatcher($dispatcher);

        (new \ReflectionMethod($subject, 'dispatchRecrawlRequest'))->invoke($subject, new ServerRequest());
    }

    public static function queueEntryDataProvider(): iterable
    {
        $resultData = json_encode([
            'content' => json_encode([
                'errorlog' => ['404 Not Found'],
                'log' => ['User Groups: 1'],
            ]),
        ]);
        yield 'not crawled' => [
            'showResultLog' => '1',
            'resultData' => '',
            'expectedResultData' => null,
            'expectedStatus' => '-',
        ];
        yield 'crawled, with result log' => [
            'showResultLog' => '1',
            'resultData' => $resultData,
            'expectedResultData' => [
                'content' => [
                    'errorlog' => ['404 Not Found'],
                    'log' => ['User Groups: 1'],
                ],
            ],
            'expectedStatus' => '404 Not Found',
        ];
        yield 'crawled, without result log' => [
            'showResultLog' => '0',
            'resultData' => $resultData,
            'expectedResultData' => [
                'content' => [
                    'errorlog' => ['404 Not Found'],
                ],
            ],
            'expectedStatus' => '404 Not Found',
        ];
        yield 'return value of a callback' => [
            'showResultLog' => '0',
            'resultData' => '{"content":"\\"Hi\\""}',
            'expectedResultData' => [
                'content' => 'Hi',
            ],
            'expectedStatus' => 'Error - no info, sorry!',
        ];
    }

    #[DataProvider('queueEntryDataProvider')]
    #[Test]
    public function decodeQueueEntryDecodesParametersAndResult(
        string $showResultLog,
        string $resultData,
        ?array $expectedResultData,
        string $expectedStatus
    ): void {
        $subject = (new \ReflectionClass(BackendModuleCrawlerLogController::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($subject, 'showResultLog'))->setValue($subject, $showResultLog);

        $decoded = (new \ReflectionMethod($subject, 'decodeQueueEntry'))->invoke($subject, [
            'qid' => 12,
            'parameters' => '{"url":"https:\\/\\/example.com\\/"}',
            'result_data' => $resultData,
        ]);

        self::assertSame([[
            'qid' => 12,
            'parameters' => [
                'url' => 'https://example.com/',
            ],
            'result_data' => $expectedResultData,
        ], $expectedStatus], $decoded);
    }

    #[Test]
    public function getQueueEntryReturnsNoEntryAndNotCrawledStatusIfQueueEntryDoesNotExist(): void
    {
        $subject = $this->createSubjectWithQueryBuilder($this->createQueryBuilder('12', false), '1');

        self::assertSame([[], '-'], (new \ReflectionMethod($subject, 'getQueueEntry'))->invoke($subject, '12'));
    }

    #[Test]
    public function getQueueEntryReturnsDecodedQueueEntryAndItsStatus(): void
    {
        $subject = $this->createSubjectWithQueryBuilder($this->createQueryBuilder('12', [
            'qid' => 12,
            'parameters' => '{"url":"https:\\/\\/example.com\\/"}',
            'result_data' => json_encode([
                'content' => json_encode([
                    'errorlog' => ['404 Not Found'],
                ]),
            ]),
        ]), '1');

        self::assertSame([[
            'qid' => 12,
            'parameters' => [
                'url' => 'https://example.com/',
            ],
            'result_data' => [
                'content' => [
                    'errorlog' => ['404 Not Found'],
                ],
            ],
        ], '404 Not Found'], (new \ReflectionMethod($subject, 'getQueueEntry'))->invoke($subject, '12'));
    }

    private function createSubjectWithQueryBuilder(
        QueryBuilder $queryBuilder,
        string $showResultLog
    ): BackendModuleCrawlerLogController {
        $subject = (new \ReflectionClass(BackendModuleCrawlerLogController::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($subject, 'queryBuilder'))->setValue($subject, $queryBuilder);
        (new \ReflectionProperty($subject, 'showResultLog'))->setValue($subject, $showResultLog);
        return $subject;
    }

    /**
     * A query builder that selects the queue entry with the given qid and returns the given row
     *
     * @param array<string, mixed>|false $row
     */
    private function createQueryBuilder(string $queueId, array|false $row): QueryBuilder
    {
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder->expects(self::once())->method('eq')->with('qid', ':qid')->willReturn('qid = :qid');
        $result = $this->createStub(Result::class);
        $result->method('fetchAssociative')->willReturn($row);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->expects(self::once())->method('createNamedParameter')->with($queueId)->willReturn(':qid');
        $queryBuilder->expects(self::once())->method('from')->with('tx_crawler_queue')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('select')->with('*')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('where')->with('qid = :qid')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('executeQuery')->willReturn($result);
        return $queryBuilder;
    }

    private function createSubjectWithDispatcher(
        CrawlUrlMessageDispatcher $dispatcher
    ): BackendModuleCrawlerLogController {
        $subject = (new \ReflectionClass(BackendModuleCrawlerLogController::class))->newInstanceWithoutConstructor();
        \Closure::bind(
            static function (BackendModuleCrawlerLogController $subject) use ($dispatcher): void {
                $subject->crawlUrlMessageDispatcher = $dispatcher;
            },
            null,
            BackendModuleCrawlerLogController::class
        )($subject);

        return $subject;
    }
}
