<?php

/*
 * Non-Composer (classic mode) installations get the libraries of the extension from
 * Resources/Private/Php/Libraries/vendor, which `composer extension-create-libs` builds from
 * Resources/Private/Php/Libraries/composer.json. TYPO3 only loads that vendor folder for the
 * packages listed in extra.typo3/cms.Package.providesPackages of composer.json.
 *
 * Every library in "require" of composer.json (except PHP, ext-* and typo3/cms-*) therefore has to be
 * - in Resources/Private/Php/Libraries/composer.json with the same version constraint, and
 * - in providesPackages, pointing to Resources/Private/Php/Libraries/vendor.
 */

$exitCode = 0;
$librariesVendor = 'Resources/Private/Php/Libraries/vendor';

$mainComposerJson = __DIR__ . '/../../composer.json';
$libsComposerJson = __DIR__ . '/../../Resources/Private/Php/Libraries/composer.json';

$mainRequirements = json_decode(file_get_contents($mainComposerJson), true);
$libsRequirements = json_decode(file_get_contents($libsComposerJson), true);
$providesPackages = $mainRequirements['extra']['typo3/cms']['Package']['providesPackages'] ?? [];

foreach ($mainRequirements['require'] as $package => $version) {

    if (
        $package === 'php'
        || false !== strpos($package, 'ext-')
        || false !== strpos($package, 'typo3/cms')
        || false !== strpos($package, 'cweagans/composer-patches')
    ) {
        continue;
    }

    if (!array_key_exists($package, $libsRequirements['require'])
        || $libsRequirements['require'][$package] !== $version
    ) {
        echo "$package:$version is missing in Resources/Private/Php/Libraries/composer.json" . chr(10);
        $exitCode = 1;
    }

    if (($providesPackages[$package] ?? null) !== $librariesVendor) {
        echo "$package is missing in extra.typo3/cms.Package.providesPackages of composer.json (\"$librariesVendor\")" . chr(10);
        $exitCode = 1;
    }
}

foreach (array_keys($libsRequirements['require']) as $package) {
    if (($providesPackages[$package] ?? null) !== $librariesVendor) {
        echo "$package of Resources/Private/Php/Libraries/composer.json is missing in extra.typo3/cms.Package.providesPackages of composer.json (\"$librariesVendor\")" . chr(10);
        $exitCode = 1;
    }
}

exit($exitCode);
