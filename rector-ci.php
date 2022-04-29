<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\Property\RemoveSetterOnlyPropertyAndMethodCallRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Set\ValueObject\SetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(
        Option::PATHS,
        [
            __DIR__ . '/Classes',
            __DIR__ . '/Configuration',
            __DIR__ . '/Tests'
        ]
    );

    $containerConfigurator->import(SetList::DEAD_CODE);
    $containerConfigurator->import(SetList::PHP_72);
    $containerConfigurator->import(SetList::PHP_73);
    $containerConfigurator->import(SetList::PHP_74);
    $containerConfigurator->import(Typo3SetList::TYPO3_76);
    $containerConfigurator->import(Typo3SetList::TYPO3_87);
    $containerConfigurator->import(Typo3SetList::TYPO3_95);

    $parameters->set(Option::AUTO_IMPORT_NAMES, false);

    $parameters->set(
        Option::AUTOLOAD_PATHS,
        [
            __DIR__ . '/Classes',
        ]
    );

    $parameters->set(
        Option::SKIP,
        [
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
            RemoveUnreachableStatementRector::class => [
                __DIR__ . '/Tests/Unit/CrawlStrategy/SubProcessExecutionStrategyTest.php'
            ],
            RemoveUnusedPrivatePropertyRector::class => [
                __DIR__ . '/Classes/Hooks/ProcessCleanUpHook.php'
            ],
            RecastingRemovalRector::class => [
                __DIR__ . '/Classes/Backend/RequestForm/LogRequestForm.php'
            ]
        ]
    );

    $services = $containerConfigurator->services();

    $services->set(RemoveUnusedPrivatePropertyRector::class);

};
