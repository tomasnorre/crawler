<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Command;

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

use AOE\Crawler\Command\TypedInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(TypedInput::class)]
final class TypedInputTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('stringValueDataProvider')]
    public function getStringArgumentCastsValue(mixed $value, string $expected): void
    {
        self::assertSame($expected, $this->createTypedInput([
            'value' => $value,
        ])->getStringArgument('value'));
    }

    #[Test]
    #[DataProvider('intValueDataProvider')]
    public function getIntArgumentCastsValue(mixed $value, int $expected): void
    {
        self::assertSame($expected, $this->createTypedInput([
            'value' => $value,
        ])->getIntArgument('value'));
    }

    #[Test]
    #[DataProvider('stringValueDataProvider')]
    public function getStringOptionCastsValue(mixed $value, string $expected): void
    {
        self::assertSame($expected, $this->createTypedInput([
            '--option' => $value,
        ])->getStringOption('option'));
    }

    #[Test]
    #[DataProvider('intValueDataProvider')]
    public function getIntOptionCastsValue(mixed $value, int $expected): void
    {
        self::assertSame($expected, $this->createTypedInput([
            '--option' => $value,
        ])->getIntOption('option'));
    }

    #[Test]
    public function getStringArgumentReturnsDefaultIfArgumentIsMissing(): void
    {
        $typedInput = $this->createTypedInput([]);

        self::assertSame('', $typedInput->getStringArgument('value'));
        self::assertSame('fallback', $typedInput->getStringArgument('value', 'fallback'));
    }

    #[Test]
    public function getIntArgumentReturnsDefaultIfArgumentIsMissing(): void
    {
        $typedInput = $this->createTypedInput([]);

        self::assertSame(0, $typedInput->getIntArgument('value'));
        self::assertSame(42, $typedInput->getIntArgument('value', 42));
    }

    #[Test]
    public function getStringOptionReturnsDefaultIfOptionIsMissing(): void
    {
        $typedInput = $this->createTypedInput([]);

        self::assertSame('', $typedInput->getStringOption('option'));
        self::assertSame('queue', $typedInput->getStringOption('option', 'queue'));
    }

    #[Test]
    public function getIntOptionReturnsDefaultIfOptionIsMissing(): void
    {
        $typedInput = $this->createTypedInput([]);

        self::assertSame(0, $typedInput->getIntOption('option'));
        self::assertSame(30, $typedInput->getIntOption('option', 30));
    }

    #[Test]
    public function defaultIsNotUsedIfValueIsGiven(): void
    {
        $typedInput = $this->createTypedInput([
            'value' => '7',
            '--option' => '0',
        ]);

        self::assertSame('7', $typedInput->getStringArgument('value', 'fallback'));
        self::assertSame(7, $typedInput->getIntArgument('value', 42));
        self::assertSame('0', $typedInput->getStringOption('option', 'queue'));
        self::assertSame(0, $typedInput->getIntOption('option', 30));
    }

    #[Test]
    public function arrayValuesAreReplacedByTheDefault(): void
    {
        $definition = new InputDefinition([
            new InputArgument('values', InputArgument::IS_ARRAY),
            new InputOption('options', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
        ]);
        $typedInput = new TypedInput(new ArrayInput([
            'values' => ['1', '2'],
            '--options' => ['3', '4'],
        ], $definition));

        self::assertSame('fallback', $typedInput->getStringArgument('values', 'fallback'));
        self::assertSame(42, $typedInput->getIntArgument('values', 42));
        self::assertSame('fallback', $typedInput->getStringOption('options', 'fallback'));
        self::assertSame(42, $typedInput->getIntOption('options', 42));
    }

    public static function stringValueDataProvider(): iterable
    {
        yield 'string' => ['defaultConfiguration', 'defaultConfiguration'];
        yield 'numeric string' => ['7', '7'];
        yield 'empty string' => ['', ''];
        yield 'integer' => [7, '7'];
    }

    public static function intValueDataProvider(): iterable
    {
        yield 'numeric string' => ['7', 7];
        yield 'negative numeric string' => ['-3', -3];
        yield 'integer' => [99, 99];
        yield 'non numeric string' => ['abc', 0];
        yield 'numeric prefix' => ['12abc', 12];
        yield 'empty string' => ['', 0];
    }

    private function createTypedInput(array $parameters): TypedInput
    {
        $definition = new InputDefinition([
            new InputArgument('value', InputArgument::OPTIONAL),
            new InputOption('option', null, InputOption::VALUE_OPTIONAL),
        ]);

        return new TypedInput(new ArrayInput($parameters, $definition));
    }
}
