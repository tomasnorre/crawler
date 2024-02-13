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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
                    $extensionConfiguration['value'] . ' [' . $extensionConfiguration['key'] . ']',
                    $extensionConfiguration['key'],
                    $this->getExtensionIcon($extensionKey),
                ];
            }
        }

        return $configuration;
    }

    /**
     * Get path to ext_icon.gif from processing instruction key
     *
     * @param string $extensionKey Like staticfilecache or indexed_search
     * @return string
     */
    private function getExtensionIcon($extensionKey)
    {
        return ExtensionManagementUtility::getExtensionIcon(ExtensionManagementUtility::extPath($extensionKey), true);
    }
}
