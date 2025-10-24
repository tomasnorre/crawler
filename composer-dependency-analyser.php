<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    //// Adjusting scanned paths
    ->addPathToScan(__DIR__ . '/Classes', isDev: false)
    //->addPathToExclude(__DIR__ . '/samples')
    ->disableComposerAutoloadPathScan() // disable automatic scan of autoload & autoload-dev paths from composer.json
    ->setFileExtensions(['php']); // applies only to directory scanning, not directly listed files

    //// Ignoring errors
    //->ignoreErrors([ErrorType::DEV_DEPENDENCY_IN_PROD])
    //->ignoreErrorsOnPath(__DIR__ . '/cache/DIC.php', [ErrorType::SHADOW_DEPENDENCY])
    //->ignoreErrorsOnPackage('symfony/polyfill-php73', [ErrorType::UNUSED_DEPENDENCY])
    //->ignoreErrorsOnPackageAndPath('symfony/console', __DIR__ . '/src/OptionalCommand.php', [ErrorType::SHADOW_DEPENDENCY])
    //->ignoreErrorsOnExtension('ext-intl', [ErrorType::SHADOW_DEPENDENCY])
    //->ignoreErrorsOnExtensionAndPath('ext-sqlite3', __DIR__ . '/tests',  [ErrorType::SHADOW_DEPENDENCY])

    //// Ignoring unknown symbols
    //->ignoreUnknownClasses(['Memcached'])
    //->ignoreUnknownClassesRegex('~^DDTrace~')
    //->ignoreUnknownFunctions(['opcache_invalidate'])
    //->ignoreUnknownFunctionsRegex('~^opcache_~')

    //// Adjust analysis
    //->enableAnalysisOfUnusedDevDependencies() // dev packages are often used only in CI, so this is not enabled by default
    //->disableReportingUnmatchedIgnores() // do not report ignores that never matched any error
    //->disableExtensionsAnalysis() // do not analyse ext-* dependencies

    //// Use symbols from yaml/xml/neon files
    // - designed for DIC config files (see below)
    // - beware that those are not validated and do not even trigger unknown class error
    //->addForceUsedSymbols($classesExtractedFromNeonJsonYamlXmlEtc);
