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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class CrawlerQueueItem
 *
 * @package AOE\Crawler\Domain\Model
 */
class CrawlerQueueItem extends AbstractEntity
{
    /**
     * @var integer
     */
    protected $pageUid = '';

    /**
     * CrawlerQueueItem constructor
     *
     * @param integer $pageUid
     */
    public function __construct($pageUid = 0)
    {
        $this->setPageUid($pageUid);
    }

    /**
     * @return integer
     */
    public function getPageUid()
    {
        return $this->pageUid;
    }

    /**
     * @param $pageUid
     * @return void
     */
    public function setPageUid($pageUid)
    {
        $this->pageUid = $pageUid;
    }
}
