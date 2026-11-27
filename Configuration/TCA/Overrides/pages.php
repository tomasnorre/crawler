<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

$languageFile = 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:';

ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_crawler_exclude' => [
        'exclude' => true,
        'label' => $languageFile . 'pages.tx_crawler_exclude',
        'description' => $languageFile . 'pages.tx_crawler_exclude.description',
        'l10n_mode' => 'exclude',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
    'tx_crawler_exclude_subpages' => [
        'exclude' => true,
        'label' => $languageFile . 'pages.tx_crawler_exclude_subpages',
        'description' => $languageFile . 'pages.tx_crawler_exclude_subpages.description',
        'l10n_mode' => 'exclude',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
]);

$GLOBALS['TCA']['pages']['palettes']['crawler'] = [
    'label' => $languageFile . 'pages.palettes.crawler',
    'showitem' => 'tx_crawler_exclude, tx_crawler_exclude_subpages',
];

ExtensionManagementUtility::addToAllTCAtypes('pages', '--palette--;;crawler', '', 'after:palette:miscellaneous');
