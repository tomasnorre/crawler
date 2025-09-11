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
 * @internal since v12.0.10
 */
class UnixProcessManager implements ProcessManagerInterface
{
    public function processExists(int $pid): bool
    {
        return file_exists('/proc/' . $pid);
    }

    public function killProcess(int $pid): void
    {
        posix_kill($pid, 9);
    }

    public function findDispatcherProcesses(): array
    {
        $returnArray = [];
        if (exec('which ps')) {
            // ps command is defined
            exec("ps aux | grep 'typo3 crawler:processQueue'", $returnArray);
        } else {
            trigger_error(
                'Crawler is unable to locate the ps command to clean up orphaned crawler processes.',
                E_USER_WARNING
            );
        }

        return $returnArray;
    }
}
