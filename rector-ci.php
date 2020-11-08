<?php

declare(strict_types=1);

use Rector\DeadCode\Rector\Class_\RemoveSetterOnlyPropertyAndMethodCallRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('paths', [__DIR__ . '/Classes', __DIR__ . '/Configuration', __DIR__ . '/Tests']);

    $parameters->set('sets', ['dead-code']);

    $parameters->set('auto_import_names', false);

    $parameters->set('autoload_paths', [__DIR__ . '/Classes', __DIR__ . '/Tests/Acceptance/Support/', __DIR__ . '/Tests/Acceptance/Support/_generated']);

    $parameters->set('exclude_paths', [__DIR__ . '/Tests/Functional/Fixtures/Extensions/typo3_console/ext_emconf.php', __DIR__ . '/Classes/Worker/CrawlerWorker.php', __DIR__ . '/Classes/Command/ProcessQueueCommand.php', __DIR__ . '/Classes/Controller/CrawlerController.php', __DIR__ . '/Classes/Domain/Model/Reason.php', __DIR__ . '/Classes/Utility/SignalSlotUtility.php', __DIR__ . '/Tests/Functional/Api/CrawlerApiTest.php', __DIR__ . '/Tests/Acceptance/Support/_generated']);

    $services = $containerConfigurator->services();

    $services->set(RemoveUnusedPrivatePropertyRector::class);

    $services->set(RemoveSetterOnlyPropertyAndMethodCallRector::class);

};
