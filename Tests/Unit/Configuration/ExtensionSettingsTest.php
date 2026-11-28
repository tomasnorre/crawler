<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Configuration;

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

use AOE\Crawler\Configuration\ExtensionSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(ExtensionSettings::class)]
class ExtensionSettingsTest extends UnitTestCase
{
    #[Test]
    public function constructorHasDefaultsOfExtConfTemplate(): void
    {
        self::assertEquals(
            new ExtensionSettings(
                frontendBasePath: '/',
                crawlHiddenPages: false,
                makeDirectRequests: false,
                maxCompileUrls: 10_000,
                enableTimeslot: true,
                cleanUpOldQueueEntries: true,
                cleanUpProcessedAge: 2,
                cleanUpScheduledAge: 7,
            ),
            new ExtensionSettings()
        );
    }

    #[Test]
    public function fromArrayWithoutSettingsReturnsDefaults(): void
    {
        self::assertEquals(new ExtensionSettings(), ExtensionSettings::fromArray([]));
    }

    #[Test]
    public function fromArrayConvertsStringsOfExtensionConfiguration(): void
    {
        self::assertEquals(
            new ExtensionSettings(
                frontendBasePath: '/cms/',
                crawlHiddenPages: true,
                makeDirectRequests: true,
                maxCompileUrls: 500,
                enableTimeslot: false,
                cleanUpOldQueueEntries: false,
                cleanUpProcessedAge: 3,
                cleanUpScheduledAge: 14,
            ),
            ExtensionSettings::fromArray([
                'frontendBasePath' => '/cms/',
                'crawlHiddenPages' => '1',
                'makeDirectRequests' => '1',
                'maxCompileUrls' => '500',
                'enableTimeslot' => '0',
                'cleanUpOldQueueEntries' => '0',
                'cleanUpProcessedAge' => '3',
                'cleanUpScheduledAge' => '14',
            ])
        );
    }

    #[Test]
    public function fromArrayKeepsTypedValues(): void
    {
        self::assertEquals(
            new ExtensionSettings(
                frontendBasePath: '5',
                crawlHiddenPages: true,
                maxCompileUrls: 20,
                enableTimeslot: false,
                cleanUpProcessedAge: 0,
            ),
            ExtensionSettings::fromArray([
                'frontendBasePath' => 5,
                'crawlHiddenPages' => true,
                'maxCompileUrls' => 20,
                'enableTimeslot' => false,
                'cleanUpProcessedAge' => 0,
            ])
        );
    }

    #[Test]
    public function fromArrayReturnsDefaultsForInvalidValues(): void
    {
        self::assertEquals(
            new ExtensionSettings(),
            ExtensionSettings::fromArray([
                'frontendBasePath' => ['/cms/'],
                'crawlHiddenPages' => ['1'],
                'makeDirectRequests' => null,
                'maxCompileUrls' => 'many',
                'enableTimeslot' => ['0'],
                'cleanUpOldQueueEntries' => new \stdClass(),
                'cleanUpProcessedAge' => 'two',
                'cleanUpScheduledAge' => '',
            ])
        );
    }

    public static function maxCompileUrlsDataProvider(): iterable
    {
        yield 'lowest value' => [
            'value' => '1',
            'expected' => 1,
        ];
        yield 'zero falls back to the default' => [
            'value' => '0',
            'expected' => 10_000,
        ];
        yield 'negative is raised to one' => [
            'value' => '-5',
            'expected' => 1,
        ];
        yield 'highest value' => [
            'value' => '1000000000',
            'expected' => 1_000_000_000,
        ];
        yield 'above highest value is lowered' => [
            'value' => '1000000001',
            'expected' => 1_000_000_000,
        ];
    }

    #[DataProvider('maxCompileUrlsDataProvider')]
    #[Test]
    public function fromArrayKeepsMaxCompileUrlsInRange(string $value, int $expected): void
    {
        self::assertSame($expected, ExtensionSettings::fromArray([
            'maxCompileUrls' => $value,
        ])->maxCompileUrls);
    }
}
