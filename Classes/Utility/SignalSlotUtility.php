<?php

declare(strict_types=1);

namespace TomasNorre\Crawler\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Class SignalSlotUtility
 *
 * @codeCoverageIgnore
 * @deprecated
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
     * @deprecated
     */
    public static function emitSignal($class, $signal, array $payload = []): void
    {
        trigger_error(
            'The SignalSlots of the TYPO3 Crawler is deprecated since v9.2.1 and will be removed in v11.x,
            we will introduce psr-14 Middelware replacements when dropping support for TYPO3 9 LTS',
            E_USER_DEPRECATED
        );

        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalSlotDispatcher->dispatch($class, $signal, $payload);
    }
}
