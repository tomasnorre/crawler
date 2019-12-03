<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides a ContextMenu item
 */
class ItemProvider extends AbstractProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'crawler' => [
            'type' => 'item',
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:contextMenu.label',
            'iconIdentifier' => 'tx-crawler',
            'callbackAction' => 'crawler'
        ]
    ];

    /**
     * Item is added only for crawler configurations
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        return $this->table === 'tx_crawler_configuration';
    }

    /**
     * This needs to be lower than priority of the RecordProvider
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Adds the crawler info
     *
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        $localItems = $this->prepareItems($this->itemsConfiguration);
        return $items + $localItems;
    }

    /**
     * @inheritDoc
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $crawlerConfiguration = BackendUtility::getRecordWSOL($this->table, $this->identifier);

        $additionalParameters = [];
        $additionalParameters[] = 'SET[function]=AOE\Crawler\Backend\BackendModule';
        $additionalParameters[] = 'SET[crawlaction]=start';
        $additionalParameters[] = 'configurationSelection[]=' . $crawlerConfiguration['name'];
        return [
            'onclick' => 'top.goToModule(\'web_info\', 1, ' . GeneralUtility::quoteJSvalue('&' . implode('&', $additionalParameters)) . ');'
        ];
    }
}
