<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\Property\RemoveSetterOnlyPropertyAndMethodCallRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Set\ValueObject\SetList;
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

    $parameters->set(
        Option::SETS,
        [
            SetList::DEAD_CODE,
            SetList::PHP_72,
            SetList::PHP_73,
        ]
    );

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
            __DIR__ . '/Classes/Utility/SignalSlotUtility.php',
            __DIR__ . '/Tests/Functional/Api/CrawlerApiTest.php',
            __DIR__ . '/Tests/Acceptance',
            Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector::class => null,
            Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class => [
                __DIR__ . '/Classes/Domain/Repository/QueueRepository.php'
            ],
            \Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector::class,
        ]
    );

    $services = $containerConfigurator->services();

    $services->set(RemoveUnusedPrivatePropertyRector::class);

    $services->set(RemoveSetterOnlyPropertyAndMethodCallRector::class);

};
