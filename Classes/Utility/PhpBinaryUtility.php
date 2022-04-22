<?php

declare(strict_types=1);

namespace AOE\Crawler\Utility;

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

use AOE\Crawler\Configuration\ExtensionConfigurationProvider;
use AOE\Crawler\Exception\CommandNotFoundException;
use AOE\Crawler\Exception\ExtensionSettingsException;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class PhpBinaryUtility
{
    public static function getPhpBinary(): string
    {
        $extensionSettings = GeneralUtility::makeInstance(ExtensionConfigurationProvider::class)->getExtensionConfiguration();

        if (empty($extensionSettings)) {
            throw new ExtensionSettingsException('ExtensionSettings are empty', 1_587_066_853);
        }

        if (empty($extensionSettings['phpPath'])) {
            $phpPath = CommandUtility::getCommand($extensionSettings['phpBinary']);
            if ($phpPath === false) {
                throw new CommandNotFoundException('The phpBinary: "' . $extensionSettings['phpBinary'] . '" could not be found!', 1_587_068_215);
            }
        } else {
            $phpPath = $extensionSettings['phpPath'];
        }

        return $phpPath;
    }
}
