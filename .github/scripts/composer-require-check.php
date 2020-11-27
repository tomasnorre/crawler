<?php

$exitCode = 0;

$mainComposerJson = __DIR__ . '/../../composer.json';
$libsComposerJson = __DIR__ . '/../../Resources/Private/Php/Libraries/composer.json';

$mainRequirements = json_decode(file_get_contents($mainComposerJson), true);
$libsRequirements = json_decode(file_get_contents($libsComposerJson), true);

foreach ($mainRequirements['require'] as $package => $version) {

    if (
        false !== strpos($package, 'php')
        || false !==  strpos($package, 'ext-')
        || false !==  strpos($package, 'typo3/cms')
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

exit($exitCode);
