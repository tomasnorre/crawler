<?php

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

namespace AOE\Crawler\Domain\Repository;

/**
 * Interface for classes which hook into pageSelect and do additional getPage processing
 *
 * This is copied from the TYPO3 Core PageRepositoryGetPageHookInterface (TYPO3 10.4LTS),
 * to keep support for 9 LTS, 10LTS and 11.0+. This can be dropped in favor of the Core
 * PageRepositoryGetPageHookInterface when support for TYPO3 9LTS is dropped.
 *
 */
interface PageRepositoryGetPageHookInterface
{
    /**
     * Modifies the DB params
     *
     * @param int $uid The page ID
     * @param bool $disableGroupAccessCheck If set, the check for group access is disabled. VERY rarely used
     * @param PageRepository $parentObject Parent object
     */
    public function getPage_preProcess(&$uid, &$disableGroupAccessCheck, PageRepository $parentObject);
}
