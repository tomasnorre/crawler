<?php
namespace AOE\Crawler\ContextMenu;

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

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;

/**
 * Class ItemProvider
 *
 * @package AOE\Crawler\Context
 */
class ItemProvider extends AbstractProvider
{

    protected $itemsConfiguration = [
        'crawler' => [
            'type' => 'item',
            'label' => 'Add page to crawler queue', //todo: use label
            'iconIdentifier' => 'tx-crawler-ext-icon',
            'callbackAction' => 'crawlerAddPageToQueue'
        ],

    ];

    public function addItems(array $items): array
    {
        $this->initDisabledItems();
        $localItems = $this->prepareItems($this->itemsConfiguration);
        if (isset($items['more']['childItems'])) {
            $items['more']['childItems'] = $items['more']['childItems'] + $localItems;
        } else {
            $items += $localItems;
        }
        return $items;
    }

    public function getPriority(): int
    {
        return 70;
    }

    public function canHandle(): bool
    {
        return true;
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        return ['data-callback-module' => '\AOE\Crawler\ContextMenuActions'];
    }
}