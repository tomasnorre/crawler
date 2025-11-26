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

use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal since v9.2.5
 */
class TcaUtility
{
    /**
     * Get crawler processing instructions.
     * This function is called as a itemsProcFunc in tx_crawler_configuration.processing_instruction_filter
     *
     * @return array
     */
    public function getProcessingInstructions(array $configuration)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] as $extensionKey => $extensionConfiguration) {
                $configuration['items'][] = [
                    'label' => $extensionConfiguration['value'] . ' [' . $extensionConfiguration['key'] . ']',
                    'value' => $extensionConfiguration['key'],
                    'icon' => $this->getExtensionIcon($extensionKey),
                ];
            }
        }

        return $configuration;
    }

    private function getExtensionIcon(string $extensionKey): string
    {
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $package = $packageManager->getPackage($extensionKey);
        if ($package->getPackageIcon()) {
            return $fullIconPath = $package->getPackagePath() . $package->getPackageIcon();
        }
        return '';
    }
}
