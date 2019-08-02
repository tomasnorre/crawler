<?php
namespace AOE\Crawler\Utility;

/*
 * Copyright notice
 *
 * (c) 2018 AOE GmbH <dev@aoe.com>
 *
 * All rights reserved
 * This file is part of the TYPO3 CMS project.
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
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class TcaUtility
 *
 * @codeCoverageIgnore
 */
class TcaUtility
{
    /**
     * Get crawler processing instructions.
     * This function is called as a itemsProcFunc in tx_crawler_configuration.processing_instruction_filter
     *
     * @param array $configuration
     * @return array
     */
    public function getProcessingInstructions(array $configuration)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['procInstructions'] as $key => $value) {
                $configuration['items'][] = [$value . ' [' . $key . ']', $key, $this->getExtensionIcon($key)];
            }
        }

        return $configuration;
    }

    /**
     * Get path to ext_icon.gif from processing instruction key
     *
     * @param string $key Like tx_realurl_rebuild
     * @return string
     */
    protected function getExtensionIcon($key)
    {
        $extIcon = '';

        $parts = explode('_', $key);
        if (is_array($parts) && count($parts) > 2) {
            $extensionKey = $parts[1];
            if ('indexedsearch' === $extensionKey) {
                $extensionKey = 'indexed_search';
            }
            
            $extIcon = ExtensionManagementUtility::getExtensionIcon(ExtensionManagementUtility::extPath($extensionKey), 1);
        }

        return $extIcon;
    }
}
