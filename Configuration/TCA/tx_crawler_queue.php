<?php

declare(strict_types=1);

defined('TYPO3') or die();

return [
    'columns' => [
        'qid' => [
            'config' => [
                'type' => 'number',
            ],
        ],
        'page_id' => [
            'config' => [
                'type' => 'number',
            ],
        ],
        'parameters' => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'parameters_hash'  => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'process_scheduled'  => [
            'config' => [
                'type' => 'number',
            ],
        ],
        'process_id'  => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'process_id_completed'  => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'result_data'  => [
            'config' => [
                'type' => 'text',
            ],
        ],
        'exec_time'  => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'scheduled'  => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'configuration'  => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'configuration_hash'  => [
            'config' => [
                'type' => 'input',
            ],
        ],

        'set_id'  => [
            'label' => 'LLL:EXT:crawler/Resources/Private/Language/Backend.xlf:tx_crawler_configuration.name',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
];
