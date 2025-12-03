<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\Property\RemoveSetterOnlyPropertyAndMethodCallRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\TYPO313\v0\MigrateTypoScriptFrontendControllerReadOnlyPropertiesRector;
use Ssch\Typo3RectorTestingFramework\Set\NimutTestingFrameworkSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests'
    ])
    ->withSkip([
        __DIR__ . '/Tests/Functional/Fixtures/Extensions/typo3_console/ext_emconf.php',
        #__DIR__ . '/Classes/Command/ProcessQueueCommand.php',
        #__DIR__ . '/Classes/Controller/CrawlerController.php',
        #__DIR__ . '/Tests/Functional/Api/CrawlerApiTest.php',
        #__DIR__ . '/Tests/Acceptance',
        #__DIR__ . '/Documentation',
        #Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector::class => null,
        Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class => [
            __DIR__ . '/Classes/Domain/Repository/QueueRepository.php'
        ],
        #RemoveAlwaysTrueIfConditionRector::class => null,
        #RemoveUnreachableStatementRector::class => [
        #    __DIR__ . '/Tests/Unit/CrawlStrategy/SubProcessExecutionStrategyTest.php'
        #],
        #RemoveUnusedPrivatePropertyRector::class => [
        #    __DIR__ . '/Classes/Hooks/ProcessCleanUpHook.php'
        #],
        #RecastingRemovalRector::class => [
        #    __DIR__ . '/Classes/Backend/RequestForm/LogRequestForm.php'
        #],
        ReadOnlyClassRector::class => null,
        MigrateTypoScriptFrontendControllerReadOnlyPropertiesRector::class => null,
    ])
    ->withAutoloadPaths([
        __DIR__ . '/Classes'
    ])
    ->withImportNames(false)
    ->withSets([
        SetList::DEAD_CODE,
        LevelSetList::UP_TO_PHP_82,
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_110,
        Typo3LevelSetList::UP_TO_TYPO3_13
    ]);
