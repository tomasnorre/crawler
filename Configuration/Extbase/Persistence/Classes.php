<?php

declare(strict_types=1);

return [
    AOE\Crawler\Domain\Model\Configuration::class => [
        'tableName' => 'tx_crawler_configuration',
    ],
    AOE\Crawler\Domain\Model\Queue::class => [
        'tableName' => 'tx_crawler_queue',
    ],
];
