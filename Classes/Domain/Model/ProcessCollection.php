<?php

declare(strict_types=1);

/*
 * (c) 2021 Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

namespace AOE\Crawler\Domain\Model;

use AOE\Crawler\Exception\NoIndexFoundException;

/**
 * Class ProcessCollection
 *
 * @internal since v9.2.5
 */
class ProcessCollection extends \ArrayObject
{
    /**
     * Method to retrieve an element from the collection.
     * @param mixed $index
     * @throws NoIndexFoundException
     */
    public function offsetGet($index): Process
    {
        if (! parent::offsetExists($index)) {
            throw new NoIndexFoundException('Index "' . var_export($index, true) . '" for \AOE\Crawler\Domain\Model\Process are not available', 1593714823);
        }
        return parent::offsetGet($index);
    }

    /**
     * Method to add an element to the collection-
     *
     * @param mixed $index
     * @param Process $subject
     * @throws \InvalidArgumentException
     */
    public function offsetSet($index, $subject): void
    {
        if (! $subject instanceof Process) {
            throw new \InvalidArgumentException('Wrong parameter type given, "\AOE\Crawler\Domain\Model\Process" expected!', 1593714822);
        }

        parent::offsetSet($index, $subject);
    }

    /**
     * Method to append an element to the collection
     * @param Process $subject
     * @throws \InvalidArgumentException
     */
    public function append($subject): void
    {
        if (! $subject instanceof Process) {
            throw new \InvalidArgumentException('Wrong parameter type given, "\AOE\Crawler\Domain\Model\Process" expected!', 1593714821);
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
