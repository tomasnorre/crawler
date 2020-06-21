<?php

declare(strict_types=1);

use AOE\Crawler\Command\BuildQueueCommand;
use AOE\Crawler\Command\FlushQueueCommand;
use AOE\Crawler\Command\ProcessQueueCommand;

// This file can be removed when compatibility with TYPO3 v9 is dropped.
// The configuration can now also be found in Configuration/Services.yaml
// for TYPO3 v10+!

return [
    'crawler:buildQueue' => [
        'class' => BuildQueueCommand::class,
    ],
    'crawler:flushQueue' => [
        'class' => FlushQueueCommand::class,
    ],
    'crawler:processQueue' => [
        'class' => ProcessQueueCommand::class,
    ],
];
