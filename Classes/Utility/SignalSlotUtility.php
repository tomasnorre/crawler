<?php
namespace AOE\Crawler\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
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
    const SIGNAL_QUEUEITEM_PREPROCESS = 'queueItemPreProcess';
    const SIGNAL_QUEUEITEM_POSTPROCESS = 'queueItemPostProcess';
    const SIGNAL_INVOKE_QUEUE_CHANGE = 'invokeQueueChange';
    const SIGNAL_URL_ADDED_TO_QUEUE = 'urlAddedToQueue';
    const SIGNAL_DUPLICATE_URL_IN_QUEUE = 'duplicateUrlInQueue';
    const SIGNAL_URL_CRAWLED = 'urlCrawled';
    const SIGNAL_QUEUE_ENTRY_FLUSH = 'queueEntryFlush';

    /**
     * Emits a signal to the signalslot dispatcher
     *
     * @param string $class
     * @param string $signal
     * @param array $payload
     * @return void
     *
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public static function emitSignal($class, $signal, array $payload = [])
    {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalSlotDispatcher->dispatch($class, $signal, $payload);
    }

    /**
     * @param string $class
     * @param string $signal
     *
     * @return bool
     */
    public static function hasSignal($class, $signal)
    {
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signals = $signalSlotDispatcher->getSlots($class, $signal);

        return sizeof($signals) ? true : false;
    }
}
