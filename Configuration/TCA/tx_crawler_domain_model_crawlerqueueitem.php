<?php
defined('TYPO3_MODE') or die();

$_EXTKEY = 'crawler';

$GLOBALS['TCA']['tx_crawler_domain_model_crawlerqueueitem'] = [
    'ctrl' => [
        'title' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_queue_item',
        'label' => 'page_uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'uid',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:crawler/Resources/Public/Icons/Extension.png',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'page_uid',
    ],
    'interface' => [
        'showRecordFieldList' => 'page_uid',
    ],
    'columns' => [
        'page_uid' => [
            'exclude' => 0,
            'label' => 'pageUid',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'page_uid'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];