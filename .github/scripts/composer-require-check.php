<?php

/*
 * Non-Composer (classic mode) installations only have the packages TYPO3 ships itself, and the ones an
 * extension provides in extra.typo3/cms.Package.providesPackages of its composer.json.
 *
 * Every library in "require" of composer.json (except PHP and ext-*) therefore has to be
 * - a dependency of the typo3/cms-* packages the extension requires, or
 * - listed in providesPackages.
 *
 * Needs `composer install` first, it reads the installed packages from .Build/vendor.
 */

$exitCode = 0;

$composerJson = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);
$installedJsonPath = __DIR__ . '/../../.Build/vendor/composer/installed.json';
if (!is_file($installedJsonPath)) {
    echo "$installedJsonPath not found, run composer install first" . chr(10);
    exit(1);
}
$installedJson = json_decode(file_get_contents($installedJsonPath), true);

$installedPackages = [];
foreach ($installedJson['packages'] ?? $installedJson as $package) {
    $installedPackages[$package['name']] = $package;
}

$requirements = array_keys($composerJson['require']);
$providesPackages = $composerJson['extra']['typo3/cms']['Package']['providesPackages'] ?? [];

// Everything the required TYPO3 packages pull in is shipped by TYPO3 in classic mode
$shippedByTypo3 = [];
$queue = array_values(array_filter($requirements, static fn(string $package): bool => str_starts_with($package, 'typo3/cms-')));
while ($queue !== []) {
    $package = array_shift($queue);
    if (isset($shippedByTypo3[$package])) {
        continue;
    }
    $shippedByTypo3[$package] = true;
    foreach (array_keys($installedPackages[$package]['require'] ?? []) as $dependency) {
        $queue[] = $dependency;
    }
    foreach (array_keys($installedPackages[$package]['provide'] ?? []) as $provided) {
        $shippedByTypo3[$provided] = true;
    }
}

foreach ($requirements as $package) {
    if ($package === 'php' || str_starts_with($package, 'ext-')) {
        continue;
    }
    if (!isset($shippedByTypo3[$package]) && !array_key_exists($package, $providesPackages)) {
        echo "$package is neither shipped by TYPO3 nor listed in extra.typo3/cms.Package.providesPackages of composer.json, "
            . 'non-Composer installations would miss it' . chr(10);
        $exitCode = 1;
    }
}

exit($exitCode);
