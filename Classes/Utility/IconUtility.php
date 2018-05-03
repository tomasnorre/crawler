<?php
namespace AOE\Crawler\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Icon\IconState;
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
     * @param string $table
     * @param array $row
     *
     * @return string
     */
    public static function getIconForRecord($table, array $row)
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        return $iconFactory->getIconForRecord($table, $row, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
    }

    /**
     * @param string $identifier
     * @param string $size "large", "small" or "default", see the constants of the Icon class
     * @param string $overlayIdentifier
     * @param IconState $state
     *
     * @return \TYPO3\CMS\Core\Imaging\Icon
     */
    public static function getIcon($identifier, $size = Icon::SIZE_DEFAULT, $overlayIdentifier = null, IconState $state = null)
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        return $iconFactory->getIcon($identifier, $size, $overlayIdentifier, $state);
    }
}
