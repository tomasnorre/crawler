<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Converter;

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

use AOE\Crawler\Converter\JsonCompatibilityConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[CoversClass(JsonCompatibilityConverter::class)]
class JsonCompatibilityConverterTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    protected JsonCompatibilityConverter $subject;

    protected function setUp(): void
    {
        $this->subject = GeneralUtility::makeInstance(JsonCompatibilityConverter::class);
    }

    #[DataProvider('jsonCompatibilityConverterDataProvider')]
    #[Test]
    public function jsonCompatibilityConverterTest(string $dataString, array|bool $expected): void
    {
        self::assertEquals($expected, $this->subject->convert($dataString));
    }

    /**
     * @throws \JsonException
     */
    public static function jsonCompatibilityConverterDataProvider(): iterable
    {
        $testData = [
            'keyString' => 'valueString',
            'keyInt' => 1024,
            'keyFloat' => 19.20,
            'keyBool' => false,
        ];

        yield 'serialize() data as input' => [
            'dataString' => serialize($testData),
            'expected' => $testData,
        ];
        yield 'json_encode() data as input' => [
            'dataString' => json_encode($testData, JSON_THROW_ON_ERROR),
            'expected' => $testData,
        ];
        yield 'neither serialize() nor json_encodee' => [
            'dataString' => 'This is just a plain string',
            'expected' => false,
        ];
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function jsonCompatibilityConverterTestThrowException(): void
    {
        $this->expectExceptionCode(1_593_758_307);
        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('#^Objects are not allowed:.*__PHP_Incomplete_Class.*#');
        $this->expectExceptionMessage('This is a test object');

        $object = new \stdClass();
        $object->title = 'Test';
        $object->description = 'This is a test object';

        $serializedObject = serialize($object);
        $this->subject->convert($serializedObject);
    }
}
