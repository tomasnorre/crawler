# crawler
[![Latest Stable Version](https://poser.pugx.org/tomasnorre/crawler/v/stable)](https://packagist.org/packages/tomasnorre/crawler)
[![Total Downloads](https://poser.pugx.org/tomasnorre/crawler/downloads)](https://packagist.org/packages/tomasnorre/crawler)
[![License](https://poser.pugx.org/tomasnorre/crawler/license)](https://packagist.org/packages/tomasnorre/crawler)
![Tests](https://github.com/tomasnorre/crawler/workflows/Tests/badge.svg)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tomasnorre/crawler/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/tomasnorre/crawler/?branch=main)
[![Code Coverage](https://scrutinizer-ci.com/g/tomasnorre/crawler/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/tomasnorre/crawler/?branch=main)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FAOEpeople%2Fcrawler%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/tomasnorre/crawler/main)
![Psalm coverage](https://shepherd.dev/github/tomasnorre/crawler/coverage.svg)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/tomasnorre/crawler.svg)](http://isitmaintained.com/project/tomasnorre/crawler "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/tomasnorre/crawler.svg)](http://isitmaintained.com/project/tomasnorre/crawler "Percentage of issues still open")

Libraries and scripts for crawling the TYPO3 page tree. Used for re-caching, re-indexing, publishing applications etc.


You can include the crawler in your TYPO3 project with composer or from [TER](https://extensions.typo3.org)

```shell script
composer require tomasnorre/crawler
```

## Disclaimer

The TYPO3 Crawler is currently not working with *SQLite*, and isn't tested with *PostgreSQL*, see [1]. *MariaDB* and *MySQL* we don't have any known issues.

1) https://github.com/tomasnorre/crawler/issues/773


## Versions and Support

| Release  | TYPO3 | PHP   | Fixes will contain
|---|---|---|---|
| 11.x.y  | 10.4-11.5 | 7.4 |Features, Bugfixes, Security Updates 
| 10.x.y  | 9.5-11.0 | 7.2-7.4 |Security Updates
| 9.x.y  | 9.5-11.0  | 7.2-7.4 |As this version has same requirements as 10.x.y, there will be no further releases of this version, please update instead.
| 8.x.y  |    |  | Releases do not exist
| 7.x.y  |    |  | Releases do not exist
| 6.x.y  | 7.6-8.7   | 5.6-7.3 | Security Updates

### Documentation
Please read the [documentation](https://docs.typo3.org/p/tomasnorre/crawler/master/en-us/)

To render the documentation locally, please use the official TYPO3 Documentation rendering Docker Tool.
<https://github.com/t3docs/docker-render-documentation>

### Contributions

Please see [CONTRIBUTING.md](https://github.com/tomasnorre/crawler/blob/main/CONTRIBUTING.md)
