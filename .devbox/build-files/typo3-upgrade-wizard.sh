#!/bin/bash

version=$(ddev exec bin/typo3 -V | grep -oP 'TYPO3 CMS \K\d+')
echo "Detected version: $version"

if [[ "$version" -eq 13 ]]; then
    echo "Version is 13. Running upgrade command."
    ddev exec bin/typo3 upgrade:run
else
    echo "Version is not 13. Skipping upgrade command."
fi
