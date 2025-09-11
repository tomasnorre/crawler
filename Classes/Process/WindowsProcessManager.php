<?php

declare(strict_types=1);

namespace AOE\Crawler\Process;

/*
 * (c) 2025 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

/**
 * @codeCoverageIgnore
 */
class WindowsProcessManager implements ProcessManagerInterface
{
    public function processExists(int $pid): bool
    {
        exec('tasklist | find "' . $pid . '"', $returnArray);
        return count($returnArray) > 0 && stripos($returnArray[0], 'php') !== false;
    }

    public function killProcess(int $pid): void
    {
        exec('taskkill /PID ' . $pid);
    }

    public function findDispatcherProcesses(): array
    {
        $returnArray = [];
        exec('tasklist | find \'typo3 crawler:processQueue\'', $returnArray);
        return $returnArray;
    }
}
