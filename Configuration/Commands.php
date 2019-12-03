<?php

use AOE\Crawler\Command\BuildQueueCommand;
use AOE\Crawler\Command\FlushQueueCommand;
use AOE\Crawler\Command\ProcessQueueCommand;

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
