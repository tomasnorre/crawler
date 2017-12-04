<?php
namespace AOE\Crawler\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IconUtility
 *
 * @package AOE\Crawler\Utility
 */
class IconUtility
{
    /**
     * Renders the HTML tag to show icons for a database record
     *
     * Wrapper around core functionality to keep compatibility with TYPO3 6.2
     *
     * @param $table
     * @param array $row
     * @return string
     */
    public static function getIconForRecord($table, array $row)
    {
        if (version_compare(TYPO3_version, '7.0', '<')) {
            return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $row);
        } else {
            $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
            return $iconFactory->getIconForRecord($table, $row, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
        }
    }
}
