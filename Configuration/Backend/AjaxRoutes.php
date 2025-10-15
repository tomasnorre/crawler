<?php

use AOE\Crawler\Controller\Ajax\ProcessStatusController;

return [
    'crawler_process_status' => [
        'path' => '/crawler/process/status',
        'target' => ProcessStatusController::class . '::getProcessStatus',
        'inheritAccessFromModule' => 'web_site_crawler_process',
    ]
];
