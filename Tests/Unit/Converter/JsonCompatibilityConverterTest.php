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
use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(\AOE\Crawler\Converter\JsonCompatibilityConverter::class)]
class JsonCompatibilityConverterTest extends UnitTestCase
{
    protected \AOE\Crawler\Converter\JsonCompatibilityConverter $subject;
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = GeneralUtility::makeInstance(JsonCompatibilityConverter::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('jsonCompatibilityConverterDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function jsonCompatibilityConverterTest(string $dataString, array|bool $expected): void
    {
        self::assertEquals($expected, $this->subject->convert($dataString));
    }

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
            'dataString' => json_encode($testData),
            'expected' => $testData,
        ];
        yield 'neither serialize() nor json_encodee' => [
            'dataString' => 'This is just a plain string',
            'expected' => false,
        ];
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function jsonCompatibilityConverterTestThrowException(): void
    {
        self::expectExceptionCode(1_593_758_307);
        self::expectException(\Throwable::class);
        self::expectExceptionMessageMatches('#^Objects are not allowed:.*__PHP_Incomplete_Class.*#');
        self::expectExceptionMessage('This is a test object');

        $object = new \stdClass();
        $object->title = 'Test';
        $object->description = 'This is a test object';

        $serializedObject = serialize($object);
        $this->subject->convert($serializedObject);
    }
}
