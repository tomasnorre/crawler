<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Model;

/***************************************************************
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
 ***************************************************************/

/**
 * @internal since v9.2.5
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

    protected array $row;

    /**
     * @param array $row
     */
    public function __construct($row = [])
    {
        $this->row = $row;
    }

    public function setUid(int $uid): void
    {
        $this->row['uid'] = $uid;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->row['uid'];
    }

    /**
     * Method to set a timestamp for the creation time of this record
     *
     * @param int $time
     */
    public function setCreationDate($time): void
    {
        $this->row['crdate'] = $time;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->row['crdate'];
    }

    /**
     * This method can be used to set a user id of the user who has created this reason entry
     *
     * @param int $user_id
     */
    public function setBackendUserId($user_id): void
    {
        $this->row['cruser_id'] = $user_id;
    }

    /**
     * @return int
     */
    public function getBackendUserId()
    {
        return $this->row['cruser_id'];
    }

    /**
     * Method to set the type of the reason for this reason instance (see constances)
     *
     * @param string $string
     */
    public function setReason($string): void
    {
        $this->row['reason'] = $string;
    }

    /**
     * This method returns the attached reason text.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->row['reason'];
    }

    /**
     * This method can be used to assign a detail text to the crawler reason
     *
     * @param string $detail_text
     */
    public function setDetailText($detail_text): void
    {
        $this->row['detail_text'] = $detail_text;
    }

    /**
     * Returns the attachet detail text.
     *
     * @return string
     */
    public function getDetailText()
    {
        return $this->row['detail_text'];
    }

    /**
     * This method is used to set the uid of the queue entry
     * where the reason is relevant for.
     *
     * @param int $entry_uid
     */
    public function setQueueEntryUid($entry_uid): void
    {
        $this->row['queue_entry_uid'] = $entry_uid;
    }

    /**
     * @return int
     */
    public function getQueueEntryUid()
    {
        return $this->row['queue_entry_uid'];
    }

    /**
     * Returns the properties of the object as array
     *
     * @return array
     */
    public function getRow()
    {
        return $this->row;
    }
}
