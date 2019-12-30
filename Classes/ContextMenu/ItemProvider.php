<?php

declare(strict_types=1);

namespace AOE\Crawler\ContextMenu;

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
            'callbackAction' => 'crawler',
        ],
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
            'onclick' => 'top.goToModule(\'web_info\', 1, ' . GeneralUtility::quoteJSvalue('&' . implode('&', $additionalParameters)) . ');',
        ];
    }
}
