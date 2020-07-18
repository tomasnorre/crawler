<?php

defined('TYPO3_MODE') or die();

$isVersion9 = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 10000000;
if (!$isVersion9) {
    return[];
}


$tca = [
    'palettes' => [
        'sitemap' => [
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:pages_crawler.priority_label',
            'showitem' => 'sitemap_priority',
        ],
    ],
    'columns' => [
        'sitemap_priority' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:pages_crawler.priority',
            'description' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:pages_crawler.priority.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['0.0', '0.0'],
                    ['0.1', '0.1'],
                    ['0.2', '0.2'],
                    ['0.3', '0.3'],
                    ['0.4', '0.4'],
                    ['0.5', '0.5'],
                    ['0.6', '0.6'],
                    ['0.7', '0.7'],
                    ['0.8', '0.8'],
                    ['0.9', '0.9'],
                    ['1.0', '1.0'],
                ],
            ],
        ],
    ],
];

$GLOBALS['TCA']['pages'] = array_replace_recursive($GLOBALS['TCA']['pages'], $tca);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;Crawler,
        --palette--;;sitemap',
    '1',
    'after:nav_icon'
);
