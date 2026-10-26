#!/bin/bash

version=$(bin/typo3 -V | grep -oP 'TYPO3 CMS \K\d+')
echo "Detected version: $version"

if ! mysql -e 'SELECT * FROM tt_content;' db > /dev/null; then
    echo 'Importing Database from file'
    if [[ "$version" -eq 13 ]]; then
        echo "Version is 13. import db.sql.gz"
        gzip -dc /var/www/html/build-files/db.sql.gz | mysql db
    else
        echo "Version is 14. import db-14.sql.gz"
        gzip -dc /var/www/html/build-files/db-14.sql.gz | mysql db
    fi
fi

# Truncate Tables before test run
mysql -e "truncate tx_crawler_queue"
mysql -e "truncate tx_crawler_process"
