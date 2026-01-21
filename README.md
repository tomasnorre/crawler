# TYPO3 Crawler
[![Latest Stable Version](https://poser.pugx.org/tomasnorre/crawler/v/stable)](https://packagist.org/packages/tomasnorre/crawler)
[![Total Downloads](https://poser.pugx.org/tomasnorre/crawler/downloads)](https://packagist.org/packages/tomasnorre/crawler)
[![License](https://poser.pugx.org/tomasnorre/crawler/license)](https://packagist.org/packages/tomasnorre/crawler)
![Tests](https://github.com/tomasnorre/crawler/workflows/Tests/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/tomasnorre/crawler/badge.svg)](https://coveralls.io/github/tomasnorre/crawler)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Ftomasnorre%2Fcrawler%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/tomasnorre/crawler/main)
![Psalm coverage](https://shepherd.dev/github/tomasnorre/crawler/coverage.svg)

TYPO3 Crawler crawls the TYPO3 page tree. Used for cache warmup, indexing, publishing applications etc.


You can include the crawler in your TYPO3 project with composer or from the [TYPO3 Extension Repository](https://extensions.typo3.org/extension/crawler)

```shell script
composer require tomasnorre/crawler
```

**Crawler processes**

![backend_processlist](https://user-images.githubusercontent.com/1212481/142763110-936be57c-1e9e-4d62-afbe-4134b139fd56.png)

## Versions and Support

| Release  | TYPO3     | PHP     | Fixes will contain
|----------|-----------|---------|---|
| dev-main | 13.4-14.x | 8.2-8.5 |This is work in progress, for next major release 14.0.0
| 12.x.y   | 12.4-13.4 | 8.1-8.4 |Features, Bugfixes, Security Updates, Since 12.0.6 TYPO3 13.4, Since 12.0.7 PHP 8.4
| 11.x.y   | 10.4-11.5 | 7.4-8.1 |Security Updates, Since 11.0.3 PHP 8.1
| 10.x.y   | 9.5-11.0  | 7.2-7.4 |Security Updates
| 9.x.y    | 9.5-11.0  | 7.2-7.4 |As this version has same requirements as 10.x.y, there will be no further releases of this version, please update instead.
| 8.x.y    |           |         | Releases do not exist
| 7.x.y    |           |         | Releases do not exist
| 6.x.y    | 7.6-8.7   | 5.6-7.3 | Security Updates

### Documentation
Please read the [documentation](https://docs.typo3.org/p/tomasnorre/crawler/main/en-us/)

To render the documentation locally, please use the official TYPO3 Documentation rendering Docker Tool.
<https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/Howto/RenderingDocs/Index.html#render-documentation-with-docker>

### Contributions

Please see [CONTRIBUTING.md](https://github.com/tomasnorre/crawler/blob/main/CONTRIBUTING.md)

### Honorable Previous Maintainers

* Kasper Skaarhoj
* Daniel Poetzinger
* Fabrizio Branca
* Tolleiv Nietsch
* Timo Schmidt
* Michael Klapper
* Stefan Rotsch
