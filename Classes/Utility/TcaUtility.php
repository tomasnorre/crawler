<?php
namespace AOE\Crawler\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class TcaUtility
 *
 * @package AOE\Crawler\Utility
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
                $configuration['items'][] = array($value . ' [' . $key . ']', $key, $this->getExtensionIcon($key));
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

        if (method_exists('TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility', 'getExtensionKeyByPrefix')) {
            $parts = explode('_', $key);
            if (is_array($parts) && count($parts) > 2) {
                $extensionKey = ExtensionManagementUtility::getExtensionKeyByPrefix('tx_' . $parts[1]);
                $extIcon = ExtensionManagementUtility::extRelPath($extensionKey) . 'ext_icon.gif';
            }
        }

        return $extIcon;
    }
}
