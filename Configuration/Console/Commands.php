<?php
return [
    'controllers' => [
        \AOE\Crawler\Command\CrawlerCommandController::class
    ],
    'runLevels' => [
        'crawlerCommand' => \Helhum\Typo3Console\Core\Booting\RunLevel::LEVEL_FULL
    ],
    'bootingSteps' => [
    ]
];