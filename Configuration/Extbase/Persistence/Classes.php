<?php

declare(strict_types=1);

return [
    TomasNorre\Crawler\Domain\Model\Configuration::class => [
        'tableName' => 'tx_crawler_configuration',
    ],
    TomasNorre\Crawler\Domain\Model\Process::class => [
        'tableName' => 'tx_crawler_process',
    ],
    TomasNorre\Crawler\Domain\Model\Queue::class => [
        'tableName' => 'tx_crawler_queue',
    ],
];
