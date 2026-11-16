<?php

declare(strict_types=1);

namespace AOE\Crawler\Command;

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

use Symfony\Component\Console\Input\InputInterface;

/**
 * Reads arguments and options of a console command with a fixed type.
 *
 * Values that are not scalar (null, or an array for multi-value inputs) are replaced by the given default.
 *
 * @internal
 */
final readonly class TypedInput
{
    public function __construct(
        private InputInterface $input
    ) {
    }

    public function getStringArgument(string $name, string $default = ''): string
    {
        return $this->toString($this->input->getArgument($name), $default);
    }

    public function getIntArgument(string $name, int $default = 0): int
    {
        return $this->toInt($this->input->getArgument($name), $default);
    }

    public function getStringOption(string $name, string $default = ''): string
    {
        return $this->toString($this->input->getOption($name), $default);
    }

    public function getIntOption(string $name, int $default = 0): int
    {
        return $this->toInt($this->input->getOption($name), $default);
    }

    private function toString(mixed $value, string $default): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    private function toInt(mixed $value, int $default): int
    {
        return is_scalar($value) ? (int) $value : $default;
    }
}
