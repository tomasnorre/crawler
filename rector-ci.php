<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\Property\RemoveSetterOnlyPropertyAndMethodCallRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\Typo3RectorTestingFramework\Set\NimutTestingFrameworkSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests'
    ])
    ->withSkip([
        __DIR__ . '/Tests/Functional/Fixtures/Extensions/typo3_console/ext_emconf.php',
        __DIR__ . '/Classes/Worker/CrawlerWorker.php',
        __DIR__ . '/Classes/Command/ProcessQueueCommand.php',
        __DIR__ . '/Classes/Controller/CrawlerController.php',
        __DIR__ . '/Classes/Domain/Model/Reason.php',
        __DIR__ . '/Tests/Functional/Api/CrawlerApiTest.php',
        __DIR__ . '/Tests/Acceptance',
        Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector::class => null,
        Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class => [
            __DIR__ . '/Classes/Domain/Repository/QueueRepository.php'
        ],
        JsonThrowOnErrorRector::class,
        RemoveAlwaysTrueIfConditionRector::class => null,
        RemoveUnreachableStatementRector::class => [
            __DIR__ . '/Tests/Unit/CrawlStrategy/SubProcessExecutionStrategyTest.php'
        ],
        RemoveUnusedPrivatePropertyRector::class => [
            __DIR__ . '/Classes/Hooks/ProcessCleanUpHook.php'
        ],
        RecastingRemovalRector::class => [
            __DIR__ . '/Classes/Backend/RequestForm/LogRequestForm.php'
        ]
    ])
    ->withAutoloadPaths([
        __DIR__ . '/Classes'
    ])
    ->withImportNames(false)
    ->withSets([
        SetList::DEAD_CODE,
        LevelSetList::UP_TO_PHP_81,
        Typo3SetList::TYPO3_11,
        Typo3SetList::TYPO3_12,
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_100,
        NimutTestingFrameworkSetList::class::NIMUT_TESTING_FRAMEWORK_TO_TYPO3_TESTING_FRAMEWORK,
    ]);


/**return static function (RectorConfig $rectorConfig): void {

    $services = $rectorConfig->services();

    $services->set(RemoveUnusedPrivatePropertyRector::class);

};*/
