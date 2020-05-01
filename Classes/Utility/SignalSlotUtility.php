<?php

declare(strict_types=1);

namespace AOE\Crawler\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Class SignalSlotUtility
 *
 * @codeCoverageIgnore
 */
class SignalSlotUtility
{
    /**
     * Predefined signals
     */
    public const SIGNAL_QUEUEITEM_PREPROCESS = 'queueItemPreProcess';

    public const SIGNAL_QUEUEITEM_POSTPROCESS = 'queueItemPostProcess';

    public const SIGNAL_INVOKE_QUEUE_CHANGE = 'invokeQueueChange';

    public const SIGNAL_URL_ADDED_TO_QUEUE = 'urlAddedToQueue';

    public const SIGNAL_DUPLICATE_URL_IN_QUEUE = 'duplicateUrlInQueue';

    public const SIGNAL_URL_CRAWLED = 'urlCrawled';

    public const SIGNAL_QUEUE_ENTRY_FLUSH = 'queueEntryFlush';

    /**
     * Emits a signal to the signal slot dispatcher
     *
     * @param string $class
     * @param string $signal
     */
    public static function emitSignal($class, $signal, array $payload = []): void
    {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalSlotDispatcher->dispatch($class, $signal, $payload);
    }
}
