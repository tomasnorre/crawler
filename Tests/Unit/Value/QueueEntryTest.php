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

use AOE\Crawler\Value\QueueEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(QueueEntry::class)]
class QueueEntryTest extends UnitTestCase
{
    /**
     * The fields of a row of tx_crawler_queue, as the database returns them
     */
    private const ROW = [
        'qid' => '12',
        'page_id' => '5',
        'parameters' => '{"url":"https:\/\/example.com\/"}',
        'parameters_hash' => 'parameters-hash',
        'configuration_hash' => 'configuration-hash',
        'scheduled' => '1000000',
        'exec_time' => '1000060',
        'set_id' => '34',
        'result_data' => '{"content":"[]"}',
        'configuration' => 'default',
    ];

    #[Test]
    public function fromRowConvertsFields(): void
    {
        self::assertEquals(
            new QueueEntry(
                qid: 12,
                pageId: 5,
                parameters: '{"url":"https:\/\/example.com\/"}',
                parametersHash: 'parameters-hash',
                configurationHash: 'configuration-hash',
                scheduled: 1_000_000,
                execTime: 1_000_060,
                setId: 34,
                resultData: '{"content":"[]"}',
                configuration: 'default',
            ),
            QueueEntry::fromRow(self::ROW)
        );
    }

    #[Test]
    public function fromRowReturnsDefaultsForMissingFields(): void
    {
        self::assertEquals(new QueueEntry(), QueueEntry::fromRow([]));
    }

    #[Test]
    public function fromRowReturnsDefaultsForInvalidFields(): void
    {
        $row = array_map(static fn (): array => ['invalid'], self::ROW);

        self::assertEquals(new QueueEntry(), QueueEntry::fromRow($row));
    }

    #[Test]
    public function fromRowIgnoresNonNumericIntegerFields(): void
    {
        self::assertSame(0, QueueEntry::fromRow([
            'page_id' => 'five',
        ])->pageId);
    }

    #[Test]
    public function fromRowConvertsScalarStringFields(): void
    {
        self::assertSame('5', QueueEntry::fromRow([
            'configuration' => 5,
        ])->configuration);
    }

    #[Test]
    public function toRowReturnsAllFields(): void
    {
        self::assertSame([
            'qid' => 12,
            'page_id' => 5,
            'parameters' => '{"url":"https:\/\/example.com\/"}',
            'parameters_hash' => 'parameters-hash',
            'configuration_hash' => 'configuration-hash',
            'scheduled' => 1_000_000,
            'exec_time' => 1_000_060,
            'set_id' => 34,
            'result_data' => '{"content":"[]"}',
            'configuration' => 'default',
        ], QueueEntry::fromRow(self::ROW)->toRow());
    }

    #[Test]
    public function toFieldArrayLeavesOutQid(): void
    {
        $fieldArray = QueueEntry::fromRow(self::ROW)->toFieldArray();

        self::assertArrayNotHasKey('qid', $fieldArray);
        self::assertSame(5, $fieldArray['page_id']);
        self::assertCount(9, $fieldArray);
    }

    #[Test]
    public function getQueueParametersDecodesParameters(): void
    {
        self::assertSame('https://example.com/', QueueEntry::fromRow(self::ROW)->getQueueParameters()?->getUrl());
    }

    #[Test]
    public function getQueueParametersReturnsNullWithoutParameters(): void
    {
        self::assertNull((new QueueEntry())->getQueueParameters());
    }

    #[Test]
    public function getCrawlerIdHashesQidSetIdAndEncryptionKey(): void
    {
        $subject = new QueueEntry(qid: 12, setId: 34);

        self::assertSame(md5('12|34|encryption-key'), $subject->getCrawlerHash('encryption-key'));
        self::assertSame('12:' . md5('12|34|encryption-key'), $subject->getCrawlerId('encryption-key'));
    }
}
