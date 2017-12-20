<?php

/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2009, AOE media GmbH <dev@aoemedia.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 * Class tx_crawler_domain_process_collection
 */
class tx_crawler_domain_process_collection extends ArrayObject
{

    /**
     * Method to retrieve an element from the collection.
     *
     * @throws Exception
     * @return tx_crawler_domain_process
     */
    public function offsetGet($index)
    {
        if (! parent::offsetExists($index)) {
            throw new Exception('Index "' . var_export($index, true) . '" for tx_crawler_domain_process are not available');
        }
        return parent::offsetGet($index);
    }

    /**
     * Method to add an element to the collection-
     *
     * @param mixed $index
     * @param tx_crawler_domain_process $subject
     * @throws InvalidArgumentException
     * @return void
     */
    public function offsetSet($index, $subject)
    {
        if (! $subject instanceof tx_crawler_domain_process) {
            throw new InvalidArgumentException('Wrong parameter type given, "tx_crawler_domain_process" expected!');
        }
        parent::offsetSet($index, $subject);
    }

    /**
     * Method to append an element to the collection
     * @param tx_crawler_domain_process $subject
     * @throws InvalidArgumentException
     * @return void
     */
    public function append($subject)
    {
        if (! $subject instanceof tx_crawler_domain_process) {
            throw new InvalidArgumentException('Wrong parameter type given, "tx_crawler_domain_process" expected!');
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
            $result[] = $value->getProcess_id();
        }
        return $result;
    }
}
