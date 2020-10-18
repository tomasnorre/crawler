<?php

declare(strict_types=1);

namespace AOE\Crawler\Backend\RequestForm;

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

use AOE\Crawler\Controller\CrawlerController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AbstractRequestForm
{
    /**
     * @var CrawlerController
     */
    protected $crawlerController;

    protected function findCrawler(): CrawlerController
    {
        if (! $this->crawlerController instanceof CrawlerController) {
            $this->crawlerController = GeneralUtility::makeInstance(CrawlerController::class);
        }
        return $this->crawlerController;
    }
}
