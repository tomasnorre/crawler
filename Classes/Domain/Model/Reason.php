<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Model;

/*
 *  Copyright notice
 *
 *  (c) 2019 AOE GmbH <dev@aoe.com>
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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * @internal since v9.2.5
 *
 * @psalm-api data object passed to integrators with InvokeQueueChangeEvent
 */
class Reason
{
    /**
     * THE CONSTANTS REPRESENT THE KIND OF THE REASON
     *
     * Convention for own states: <extensionkey>_<reason>
     */
    public const REASON_DEFAULT = 'crawler_default_reason';
    public const REASON_GUI_SUBMIT = 'crawler_gui_submit_reason';
    public const REASON_CLI_SUBMIT = 'crawler_cli_submit_reason';

    protected int $uid;

    protected int $creationDate;

    protected int $backendUserId;

    protected string $reason;

    protected string $detailText;

    protected int $queueEntryUid;

    /**
     * @param array<string, mixed> $row
     */
    public function __construct(array $row = [])
    {
        $this->uid = $this->intValue($row, 'uid');
        $this->creationDate = $this->intValue($row, 'crdate');
        $this->backendUserId = $this->intValue($row, 'cruser_id');
        $this->reason = $this->stringValue($row, 'reason');
        $this->detailText = $this->stringValue($row, 'detail_text');
        $this->queueEntryUid = $this->intValue($row, 'queue_entry_uid');
    }

    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * Method to set a timestamp for the creation time of this record
     */
    public function setCreationDate(int $time): void
    {
        $this->creationDate = $time;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    /**
     * This method can be used to set a user id of the user who has created this reason entry
     */
    public function setBackendUserId(int $userId): void
    {
        $this->backendUserId = $userId;
    }

    public function getBackendUserId(): int
    {
        return $this->backendUserId;
    }

    /**
     * Method to set the type of the reason for this reason instance (see constants)
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * This method returns the attached reason text.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * This method can be used to assign a detail text to the crawler reason
     */
    public function setDetailText(string $detailText): void
    {
        $this->detailText = $detailText;
    }

    /**
     * Returns the attached detail text.
     */
    public function getDetailText(): string
    {
        return $this->detailText;
    }

    /**
     * This method is used to set the uid of the queue entry
     * where the reason is relevant for.
     */
    public function setQueueEntryUid(int $entryUid): void
    {
        $this->queueEntryUid = $entryUid;
    }

    public function getQueueEntryUid(): int
    {
        return $this->queueEntryUid;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function intValue(array $row, string $key): int
    {
        $value = $row[$key] ?? 0;
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function stringValue(array $row, string $key): string
    {
        $value = $row[$key] ?? '';
        return is_scalar($value) ? (string) $value : '';
    }
}
