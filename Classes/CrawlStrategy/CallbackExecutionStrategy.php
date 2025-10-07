<?php

declare(strict_types=1);

namespace AOE\Crawler\CrawlStrategy;

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

/**
 * Used for hooks (e.g. crawling external files)
 * @internal since v12.0.0
 */
class CallbackExecutionStrategy
{
    /**
     * In the future, the callback should implement an interface.
     * @template T of object
     * @param class-string<T> $callbackClassName
     */
    public function fetchByCallback(string $callbackClassName, array $parameters, CrawlerController $crawlerController)
    {
        // Calling custom object
        $callBackObj = GeneralUtility::makeInstance($callbackClassName);
        return $callBackObj->crawler_execute($parameters, $crawlerController);
    }
}
