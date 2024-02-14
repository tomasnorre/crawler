<?php

declare(strict_types=1);

namespace AOE\Crawler\Utility;

/*
 * (c) 2024-     Tomas Norre Mikkelsen <tomasnorre@gmail.com>
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

/**
 * @package AOE\Crawler\Utility
 * @internal since v12.0.0
 */
class CrawlerConfigurationUtility
{
    /**
     * Returns a md5 hash generated from a serialized configuration array.
     *
     * @param array $configuration
     * @return string
     */
    public static function getConfigurationHash(array $configuration): string
    {
        unset($configuration['paramExpanded'], $configuration['URLs']);
        return md5(serialize($configuration));
    }

}
