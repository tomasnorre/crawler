<?php

$exitCode = 0;

$mainComposerJson = __DIR__ . '/../../composer.json';
$libsComposerJson = __DIR__ . '/../../Resources/Private/Php/Libraries/composer.json';

$mainRequirements = json_decode(file_get_contents($mainComposerJson), true);
$libsRequirements = json_decode(file_get_contents($libsComposerJson), true);

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
}

$librariesVendor = 'Resources/Private/Php/Libraries/vendor';
$providesPackages = $mainRequirements['extra']['typo3/cms']['Package']['providesPackages'] ?? [];

foreach (array_keys($libsRequirements['require']) as $package) {
    if (($providesPackages[$package] ?? null) !== $librariesVendor) {
        echo "$package is missing in extra.typo3/cms.Package.providesPackages of composer.json (\"$librariesVendor\")" . chr(10);
        $exitCode = 1;
    }
}

exit($exitCode);
