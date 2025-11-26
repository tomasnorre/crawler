<?php

declare(strict_types=1);

namespace AOE\Crawler\Hooks;

/*
 * (c) 2005-2021 AOE GmbH <dev@aoe.com>
 * (c) 2021-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

use AOE\Crawler\Process\Cleaner\OldProcessCleaner;
use AOE\Crawler\Process\Cleaner\OrphanProcessCleaner;

/**
 * @internal since v9.2.5
 */
class ProcessCleanUpHook implements CrawlerHookInterface
{
    public function __construct(
        private readonly OrphanProcessCleaner $orphanCleaner,
        private readonly OldProcessCleaner $oldCleaner
    ) {
    }

    #[\Override]
    public function crawler_init(): void
    {
        $this->orphanCleaner->clean();
        $this->oldCleaner->clean();
    }
}
