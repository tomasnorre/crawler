<?php
declare(strict_types=1);
namespace AOE\Crawler\Utility;

/*
 * (c) 2019 AOE GmbH <dev@aoe.com>
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

use TYPO3\CMS\Core\Utility\CommandUtility;

class PhpBinaryUtility
{
    /**
     * @param array $extensionSettings
     * @return string
     */
    public static function getPhpBinary(array $extensionSettings): string
    {
        if (empty($extensionSettings)) {
            throwException('ExtensionSettings are empty');
        }

        if (empty($extensionSettings['phpPath'])) {
            $phpPath = CommandUtility::getCommand($extensionSettings['phpBinary']);
        } else {
            $phpPath = $extensionSettings['phpPath'];
        }

        return $phpPath;
    }
}
