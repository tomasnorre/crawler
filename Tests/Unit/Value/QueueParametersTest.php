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

use AOE\Crawler\Value\QueueParameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(QueueParameters::class)]
class QueueParametersTest extends UnitTestCase
{
    /**
     * The JSON is hashed into parameters_hash, which finds duplicate queue entries.
     * It must stay the same for equal parameters, also across versions.
     */
    #[Test]
    public function forUrlEncodesParametersInStableOrder(): void
    {
        self::assertSame(
            '{"url":"https:\/\/example.com\/","feUserGroupList":"1,2","procInstructions":["tx_indexedsearch_reindex"],'
            . '"procInstrParams":{"tx_indexedsearch_reindex.":{"a":"1"}}}',
            QueueParameters::forUrl('https://example.com/', '1,2', ['tx_indexedsearch_reindex'], [
                'tx_indexedsearch_reindex.' => [
                    'a' => '1',
                ],
            ])->toJson()
        );
    }

    #[Test]
    public function forUrlLeavesOutEmptyUserGroupsAndMissingProcessingInstructionParameters(): void
    {
        self::assertSame(
            '{"url":"https:\/\/example.com\/","procInstructions":[""]}',
            QueueParameters::forUrl('https://example.com/')->toJson()
        );
    }

    #[Test]
    public function forUrlKeepsEmptyProcessingInstructionParameters(): void
    {
        self::assertSame(
            '{"url":"\/","procInstructions":[""],"procInstrParams":[]}',
            QueueParameters::forUrl('/', processingInstructionParameters: [])->toJson()
        );
    }

    #[Test]
    public function forUrlHasUrlGettersAndIsNoCallback(): void
    {
        $subject = QueueParameters::forUrl('https://example.com/', '1,2', ['tx_a', 'tx_b']);

        self::assertSame('https://example.com/', $subject->getUrl());
        self::assertSame('1,2', $subject->getFeUserGroupList());
        self::assertSame(['tx_a', 'tx_b'], $subject->getProcessingInstructions());
        self::assertFalse($subject->isCallback());
        self::assertSame('', $subject->getCallbackClassName());
    }

    #[Test]
    public function forCallbackAddsClassNameToParameters(): void
    {
        $subject = QueueParameters::forCallback('Vendor\\Callback', [
            'name' => 'Crawler',
            'version' => 14,
        ]);

        self::assertSame(
            '{"name":"Crawler","version":14,"_CALLBACKOBJ":"Vendor\\\\Callback"}',
            $subject->toJson()
        );
        self::assertTrue($subject->isCallback());
        self::assertSame('Vendor\\Callback', $subject->getCallbackClassName());
        self::assertSame([
            'name' => 'Crawler',
            'version' => 14,
        ], $subject->getCallbackParameters());
        self::assertSame('', $subject->getUrl());
    }

    #[Test]
    public function toJsonThrowsForParametersThatAreNotJsonEncodable(): void
    {
        $this->expectException(\JsonException::class);

        QueueParameters::forCallback('Vendor\\Callback', [
            'number' => NAN,
        ])->toJson();
    }

    public static function noParametersDataProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'invalid JSON' => ['{no json'];
        yield 'empty array' => ['[]'];
        yield 'string' => ['"https://example.com/"'];
        yield 'serialized string' => [serialize('Simple string')];
    }

    #[DataProvider('noParametersDataProvider')]
    #[Test]
    public function fromJsonReturnsNullWithoutParameters(string $json): void
    {
        self::assertNull(QueueParameters::fromJson($json));
    }

    #[Test]
    public function fromJsonKeepsAllParameters(): void
    {
        $parameters = [
            'url' => 'https://example.com/',
            'procInstructions' => [''],
            'addedByListener' => true,
        ];

        $subject = QueueParameters::fromJson((string) json_encode($parameters));

        self::assertSame($parameters, $subject?->toArray());
        self::assertSame(json_encode($parameters), $subject->toJson());
    }

    public static function invalidValuesDataProvider(): iterable
    {
        yield 'missing' => [[
            'other' => 1,
        ]];
        yield 'no scalars' => [[
            'url' => ['https://example.com/'],
            'feUserGroupList' => [1],
            'procInstructions' => 'tx_a',
            QueueParameters::CALLBACK_PARAMETER => ['Vendor\\Callback'],
        ]];
    }

    #[DataProvider('invalidValuesDataProvider')]
    #[Test]
    public function gettersReturnEmptyValuesForMissingOrInvalidParameters(array $parameters): void
    {
        $subject = QueueParameters::fromJson((string) json_encode($parameters));

        self::assertSame('', $subject?->getUrl());
        self::assertSame('', $subject->getFeUserGroupList());
        self::assertSame([], $subject->getProcessingInstructions());
        self::assertSame('', $subject->getCallbackClassName());
    }

    #[Test]
    public function gettersConvertScalars(): void
    {
        $subject = QueueParameters::fromJson((string) json_encode([
            'url' => 5,
            'feUserGroupList' => 1,
            'procInstructions' => [
                'a' => 'tx_a',
                'b' => 1,
                'c' => ['nested'],
                'd' => null,
            ],
        ]));

        self::assertSame('5', $subject?->getUrl());
        self::assertSame('1', $subject->getFeUserGroupList());
        self::assertSame(['tx_a', '1'], $subject->getProcessingInstructions());
    }
}
