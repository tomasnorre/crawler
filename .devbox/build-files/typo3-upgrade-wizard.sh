#!/bin/bash

version=$(bin/typo3 -V | grep -oP 'TYPO3 CMS \K\d+')
echo "Detected version: $version"

if [[ "$version" -eq 14 ]]; then
    echo "Version is 13. Running upgrade command."
    bin/typo3 upgrade:run
else
    echo "Version is not 14. Skipping upgrade command."
fi
