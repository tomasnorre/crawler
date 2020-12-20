<?php

declare(strict_types=1);

namespace AOE\Crawler\Service;

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

class ConfigurationService
{
    public static function removeDisallowedConfigurations(array $allowedConfigurations, array $configurations): array
    {
        if (count($allowedConfigurations) > 0) {
            // 	remove configuration that does not match the current selection
            foreach ($configurations as $confKey => $confArray) {
                if (! in_array($confKey, $allowedConfigurations, true)) {
                    unset($configurations[$confKey]);
                }
            }
        }
        return $configurations;
    }
}
