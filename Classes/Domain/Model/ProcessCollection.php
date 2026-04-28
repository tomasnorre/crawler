<?php

declare(strict_types=1);

namespace AOE\Crawler\Domain\Model;

use AOE\Crawler\Exception\NoIndexFoundException;
use InvalidArgumentException;

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
class ProcessCollection extends \ArrayObject
{
    /**
     * Method to retrieve an element from the collection.
     * @throws NoIndexFoundException
     */
    #[\Override]
    public function offsetGet(mixed $key): Process
    {
        if (!parent::offsetExists($key)) {
            throw new NoIndexFoundException('Index "' . var_export(
                $key,
                true
            ) . '" for \AOE\Crawler\Domain\Model\Process are not available', 1_593_714_823);
        }
        return parent::offsetGet($key);
    }

    /**
     * Method to add an element to the collection-
     *
     * @param Process $value
     * @throws InvalidArgumentException
     */
    #[\Override]
    public function offsetSet(mixed $key, $value): void
    {
        if (!$value instanceof Process) {
            throw new \InvalidArgumentException(
                'Wrong parameter type given, "\AOE\Crawler\Domain\Model\Process" expected!',
                1_593_714_822
            );
        }

        parent::offsetSet($key, $value);
    }

    /**
     * Method to append an element to the collection
     * @param Process $value
     * @throws InvalidArgumentException
     */
    #[\Override]
    public function append($value): void
    {
        if (!$value instanceof Process) {
            throw new \InvalidArgumentException(
                'Wrong parameter type given, "\AOE\Crawler\Domain\Model\Process" expected!',
                1_593_714_821
            );
        }

        parent::append($value);
    }

    /**
     * returns array of process ids of the current collection
     */
    public function getProcessIds(): array
    {
        $result = [];
        foreach ($this->getIterator() as $value) {
            $result[] = $value->getProcessId();
        }
        return $result;
    }
}
