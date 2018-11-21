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
 * Class ProcessCollection
 *
 * @package AOE\Crawler\Domain\Model
 */
class ProcessCollection extends \ArrayObject
{

    /**
     * Method to retrieve an element from the collection.
     *
     * @throws \Exception
     * @return Process
     */
    public function offsetGet($index)
    {
        if (! parent::offsetExists($index)) {
            throw new \Exception('Index "' . var_export($index, true) . '" for \AOE\Crawler\Domain\Model\Process are not available');
        }
        return parent::offsetGet($index);
    }

    /**
     * Method to add an element to the collection-
     *
     * @param mixed $index
     * @param Process $subject
     * @throws \InvalidArgumentException
     * @return void
     */
    public function offsetSet($index, $subject)
    {
        if (! $subject instanceof Process) {
            throw new \InvalidArgumentException('Wrong parameter type given, "\AOE\Crawler\Domain\Model\Process" expected!');
        }
        parent::offsetSet($index, $subject);
    }

    /**
     * Method to append an element to the collection
     * @param Process $subject
     * @throws \InvalidArgumentException
     * @return void
     */
    public function append($subject)
    {
        if (! $subject instanceof Process) {
            throw new \InvalidArgumentException('Wrong parameter type given, "\AOE\Crawler\Domain\Model\Process" expected!');
        }
        parent::append($subject);
    }

    /**
     * returns array of process ids of the current collection
     * @return array
     */
    public function getProcessIds()
    {
        $result = [];
        foreach ($this->getIterator() as $value) {
            $result[] = $value->getProcessId();
        }
        return $result;
    }
}
