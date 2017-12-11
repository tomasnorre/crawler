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

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ButtonUtility
 *
 * For button icons available see
 * https://github.com/TYPO3/TYPO3.Icons
 *
 * @package AOE\Crawler\Utility
 *
 * @codeCoverageIgnore
 */
class ButtonUtility
{
    /**
     * @param string $icon
     * @param string $title
     * @param string $onClick
     * @param string $iconSize
     * @param string $href
     * @param array $attributes
     *
     * @return $this
     */
    public static function getLinkButton($icon = '', $title = '', $onClick = '', $iconSize = Icon::SIZE_SMALL, $href = '#', $attributes = [])
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        /** @var ModuleTemplate $moduleTemplate */
        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $button = $buttonBar->makeLinkButton()
            ->setHref($href)
            ->setDataAttributes($attributes)
            ->setIcon($iconFactory->getIcon($icon, $iconSize))
            ->setTitle($title)
            ->setOnClick($onClick)
            ->setShowLabelText(true);

        return $button;
    }
}
