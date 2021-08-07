# crawler
[![Latest Stable Version](https://poser.pugx.org/aoepeople/crawler/v/stable)](https://packagist.org/packages/aoepeople/crawler)
[![Total Downloads](https://poser.pugx.org/aoepeople/crawler/downloads)](https://packagist.org/packages/aoepeople/crawler)
[![License](https://poser.pugx.org/aoepeople/crawler/license)](https://packagist.org/packages/aoepeople/crawler)
![Tests](https://github.com/AOEpeople/crawler/workflows/Tests/badge.svg)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/AOEpeople/crawler/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/AOEpeople/crawler/?branch=main)
[![Code Coverage](https://scrutinizer-ci.com/g/AOEpeople/crawler/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/AOEpeople/crawler/?branch=main)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FAOEpeople%2Fcrawler%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/AOEpeople/crawler/main)
![Psalm coverage](https://shepherd.dev/github/aoepeople/crawler/coverage.svg)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/aoepeople/crawler.svg)](http://isitmaintained.com/project/aoepeople/crawler "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/aoepeople/crawler.svg)](http://isitmaintained.com/project/aoepeople/crawler "Percentage of issues still open")

Libraries and scripts for crawling the TYPO3 page tree. Used for re-caching, re-indexing, publishing applications etc.


You can include the crawler in your TYPO3 project with composer or from [TER](https://extensions.typo3.org)

```shell script
composer require aoepeople/crawler
```

## Disclaimer

The TYPO3 Crawler is currently not working with *SQLite*, and isn't tested with *PostgreSQL*, see [1]. *MariaDB* and *MySQL* we don't have any known issues.

1) https://github.com/AOEpeople/crawler/issues/773


## Versions and Support

| Release  | TYPO3 | PHP   | Fixes will contain
|---|---|---|---|
| 10.x.y  | 9.5-11.0 | 7.2-7.4 |Features, Bugfixes, Security Updates
| 9.x.y  | 9.5-11.0  | 7.2-7.4 |Bugfixes, Security Updates
| 8.x.y  |    |  | Releases do not exist
| 7.x.y  |    |  | Releases do not exist
| 6.x.y  | 7.6-8.7   | 5.6-7.3 | Security Updates

### Documentation
Please read the [documentation](https://docs.typo3.org/typo3cms/extensions/crawler/)

To render the documentation locally, please use the official TYPO3 Documentation rendering Docker Tool.
<https://github.com/t3docs/docker-render-documentation>

### Contributions

Please see [CONTRIBUTING.md](https://github.com/AOEpeople/crawler/blob/main/CONTRIBUTING.md)
