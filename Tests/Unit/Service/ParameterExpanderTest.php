<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Service;

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

use AOE\Crawler\Service\ParameterExpander;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ParameterExpander::class)]
class ParameterExpanderTest extends UnitTestCase
{
    private ParameterExpander $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ParameterExpander();
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TCA'],
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']
        );
        parent::tearDown();
    }

    #[DataProvider('expandDataProvider')]
    #[Test]
    public function expandExpandsValuesWithoutDatabase(array $paramArray, array $expected): void
    {
        self::assertSame($expected, $this->subject->expand($paramArray, 5));
    }

    public static function expandDataProvider(): iterable
    {
        yield 'No parameters' => [[], []];
        yield 'Literal values are trimmed and kept in order of the parameters' => [
            [
                'L' => ' 1 ',
                'id' => 'abc',
            ],
            [
                'L' => ['1'],
                'id' => ['abc'],
            ],
        ];
        yield 'Integer value' => [
            [
                'L' => 2,
            ],
            [
                'L' => ['2'],
            ],
        ];
        yield 'Value only starting with a square bracket is literal' => [
            [
                'L' => '[1|2',
            ],
            [
                'L' => ['[1|2'],
            ],
        ];
        yield 'Value only ending with a square bracket is literal' => [
            [
                'L' => '1|2]',
            ],
            [
                'L' => ['1|2]'],
            ],
        ];
        yield 'List of values' => [
            [
                'L' => '[a|b|a]',
            ],
            [
                'L' => [
                    0 => 'a',
                    1 => 'b',
                ],
            ],
        ];
        yield 'Range' => [
            [
                'page' => '[1-3]',
            ],
            [
                'page' => ['1', 2, 3],
            ],
        ];
        yield 'Range with spaces' => [
            [
                'page' => '[ 1 - 2 ]',
            ],
            [
                'page' => ['1', 2],
            ],
        ];
        yield 'Reversed range' => [
            [
                'page' => '[3-1]',
            ],
            [
                'page' => ['1', 2, 3],
            ],
        ];
        yield 'Negative range' => [
            [
                'page' => '[-2--1]',
            ],
            [
                'page' => ['-2', -1],
            ],
        ];
        yield 'Range of a single value' => [
            [
                'page' => '[4-4]',
            ],
            [
                'page' => ['4'],
            ],
        ];
        yield 'Range and values' => [
            [
                'page' => '[1-2|x|2]',
            ],
            [
                'page' => [
                    0 => '1',
                    1 => 2,
                    2 => 'x',
                ],
            ],
        ];
        yield 'Expanded parameters are sorted by name' => [
            [
                'b' => '[1]',
                'a' => '[2]',
            ],
            [
                'a' => ['2'],
                'b' => ['1'],
            ],
        ];
        yield 'Table not in TCA' => [
            [
                'L' => '1',
                'tx_news' => '[_TABLE:tx_news_domain_model_news]',
            ],
            [
                'L' => ['1'],
                'tx_news' => [],
            ],
        ];
    }

    #[Test]
    public function expandLimitsRangeToOneThousandValues(): void
    {
        $values = $this->subject->expand([
            'page' => '[1-2000]',
        ], 5)['page'];

        self::assertCount(1000, $values);
        self::assertSame('1', $values[0]);
        self::assertSame(1000, $values[999]);
    }

    #[Test]
    public function expandIgnoresFieldNotInTca(): void
    {
        $GLOBALS['TCA']['tt_content'] = [
            'columns' => [],
        ];

        // No query is built, which would fail without a database
        self::assertSame([
            'L' => ['1'],
            'uid' => [],
        ], $this->subject->expand([
            'uid' => '[_TABLE:tt_content; _FIELD:header]',
            'L' => '1',
        ], 5));
    }

    #[Test]
    public function expandAddsUidsOfTableRecordsOnPage(): void
    {
        $GLOBALS['TCA']['tt_content'] = [
            'ctrl' => [
                'transOrigPointerField' => 'l18n_parent',
            ],
        ];
        $queryBuilder = $this->createQueryBuilder(
            'uid',
            'tt_content',
            'pid',
            [5],
            '',
            [[
                'uid' => 3,
            ], [
                'uid' => 1,
            ], [
                'uid' => 3,
            ]]
        );
        $queryBuilder->expects(self::never())->method('andWhere');
        $this->addConnectionPool('tt_content', $queryBuilder);

        self::assertSame([
            'L' => ['1'],
            'uid' => ['x', 3, 1],
        ], $this->subject->expand([
            'uid' => '[x|_TABLE:tt_content]',
            'L' => '1',
        ], 5));
    }

    #[Test]
    public function expandAddsFieldOfTableRecordsUsingAllOptions(): void
    {
        $GLOBALS['TCA']['tx_news'] = [
            'ctrl' => [
                'transOrigPointerField' => 'l10n_parent',
            ],
            'columns' => [
                'path_segment' => [
                    'config' => [
                        'type' => 'slug',
                    ],
                ],
            ],
        ];
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects(self::once())->method('getPageIdsRecursive')->with([12], 2)->willReturn([12, 13]);
        GeneralUtility::addInstance(PageRepository::class, $pageRepository);
        $queryBuilder = $this->createQueryBuilder(
            'path_segment',
            'tx_news',
            'storage',
            [12, 13],
            'hidden = 0',
            [[
                'path_segment' => 'first',
            ], [
                'path_segment' => 'second',
            ]],
            ['sys_category', 'sys_category_record_mm']
        );
        $queryBuilder->expects(self::once())->method('andWhere')->with('l10n_parent <= 0')->willReturnSelf();
        $this->addConnectionPool('tx_news', $queryBuilder);

        self::assertSame([
            'news' => ['first', 'second'],
        ], $this->subject->expand([
            'news' => '[_TABLE:tx_news; _FIELD:path_segment; _PID:12; _RECURSIVE:2; _PIDFIELD: storage ;'
                . ' _WHERE:hidden = 0; _ADDTABLE:sys_category, sys_category_record_mm; _ENABLELANG:1]',
        ], 5));
    }

    #[Test]
    public function expandIgnoresEnableLangForTableWithoutTranslations(): void
    {
        $GLOBALS['TCA']['tx_table'] = [
            'ctrl' => [],
        ];
        $queryBuilder = $this->createQueryBuilder('uid', 'tx_table', 'pid', [5], '', [[
            'uid' => 1,
        ]]);
        $queryBuilder->expects(self::never())->method('andWhere');
        $this->addConnectionPool('tx_table', $queryBuilder);

        self::assertSame([
            'uid' => [1],
        ], $this->subject->expand([
            'uid' => '[_TABLE:tx_table; _ENABLELANG:1]',
        ], 5));
    }

    #[Test]
    public function expandCallsExpandParametersHookForEachPart(): void
    {
        $calls = [];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['crawler/class.tx_crawler_lib.php']['expandParameters'][] =
            static function (array &$params, ParameterExpander $expander) use (&$calls): void {
                self::assertSame($expander, $params['pObj']);
                $calls[] = [$params['currentKey'], $params['currentValue'], $params['pid']];
                if ($params['currentValue'] === '_CUSTOM') {
                    $params['paramArray'][$params['currentKey']][] = 'custom';
                }
            };

        $result = $this->subject->expand([
            'L' => '1',
            'tx' => '[_CUSTOM|1-2]',
        ], 5);

        self::assertSame([['tx', '_CUSTOM', 5], ['tx', '1-2', 5]], $calls);
        self::assertSame([
            'L' => ['1'],
            'tx' => ['_CUSTOM', 'custom', '1', 2],
        ], $result);
    }

    /**
     * @param list<int> $pids
     * @param list<array<string, mixed>> $rows
     */
    private function createQueryBuilder(
        string $fieldName,
        string $table,
        string $pidField,
        array $pids,
        string $where,
        array $rows,
        array $additionalTables = []
    ): QueryBuilder&MockObject {
        $deletedRestriction = $this->createStub(DeletedRestriction::class);
        GeneralUtility::addInstance(DeletedRestriction::class, $deletedRestriction);
        $restrictions = $this->createMock(QueryRestrictionContainerInterface::class);
        $restrictions->expects(self::once())->method('removeAll')->willReturnSelf();
        $restrictions->expects(self::once())->method('add')->with($deletedRestriction)->willReturnSelf();

        $expressionBuilder = $this->createStub(ExpressionBuilder::class);
        $expressionBuilder->method('in')->willReturnCallback(
            static fn (string $field, string $value): string => $field . ' IN (' . $value . ')'
        );
        $expressionBuilder->method('lte')->willReturnCallback(
            static fn (string $field, int $value): string => $field . ' <= ' . $value
        );

        $result = $this->createStub(Result::class);
        $result->method('fetchAssociative')->willReturnOnConsecutiveCalls(...[...$rows, false]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRestrictions')->willReturn($restrictions);
        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->expects(self::once())->method('createNamedParameter')
            ->with($pids, ArrayParameterType::INTEGER)
            ->willReturn(':pids');
        $queryBuilder->expects(self::once())->method('select')->with($fieldName)->willReturnSelf();
        $fromTables = [$table, ...$additionalTables];
        $queryBuilder->expects(self::exactly(count($fromTables)))->method('from')->willReturnCallback(
            static function (string $fromTable) use (&$fromTables, $queryBuilder): QueryBuilder {
                self::assertSame(array_shift($fromTables), $fromTable);
                return $queryBuilder;
            }
        );
        $queryBuilder->expects(self::once())->method('where')
            ->with($pidField . ' IN (:pids)', $where)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())->method('executeQuery')->willReturn($result);

        return $queryBuilder;
    }

    private function addConnectionPool(string $table, QueryBuilder $queryBuilder): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects(self::once())->method('getQueryBuilderForTable')
            ->with($table)
            ->willReturn($queryBuilder);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool);
    }
}
