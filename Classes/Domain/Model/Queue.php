<?php
namespace AOE\Crawler\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
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

/**
 * Class Queue
 *
 * @package AOE\Crawler\Domain\Model
 */
class Queue
{
    /**
     * @var array
     */
    protected $row;

    /**
     * @param array $row
     */
    public function __construct($row = [])
    {
        $this->row = $row;
    }

    /**
     * Returns the execution time of the record as int value
     *
     * @return integer
     */
    public function getExecutionTime()
    {
        return $this->row['exec_time'];
    }
}
