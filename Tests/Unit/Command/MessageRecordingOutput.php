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

use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Records the unformatted lines written with writeln(), for tests.
 *
 * The formatted output can't tell an unclosed style tag from a closed one, the unformatted lines can.
 */
final class MessageRecordingOutput extends BufferedOutput
{
    /**
     * @var list<string>
     */
    public array $lines = [];

    #[\Override]
    public function writeln(string|iterable $messages, int $options = self::OUTPUT_NORMAL): void
    {
        foreach (is_string($messages) ? [$messages] : $messages as $message) {
            $this->lines[] = $message;
        }
        parent::writeln($messages, $options);
    }
}
