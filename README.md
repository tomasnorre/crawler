# crawler
Libraries and scripts for crawling the TYPO3 page tree. Used for re-caching, re-indexing, publishing applications etc.

## Build information
[![Build Status](https://travis-ci.org/AOEpeople/crawler.svg?branch=typo3v9)](https://travis-ci.org/AOEpeople/crawler)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/AOEpeople/crawler/badges/quality-score.png?b=typo3v9)](https://scrutinizer-ci.com/g/AOEpeople/crawler/?branch=typo3v9)
[![Code Coverage](https://scrutinizer-ci.com/g/AOEpeople/crawler/badges/coverage.png?b=typo3v9)](https://scrutinizer-ci.com/g/AOEpeople/crawler/?branch=typo3v9)

### Wiki
Please see the [Wiki Pages](https://github.com/AOEpeople/crawler/wiki) for Release notes and Known issues.

### Documentation
Please read the [documentation](https://docs.typo3.org/typo3cms/extensions/crawler/)

To render the documentation locally, please use the official TYPO3 Documentation rendering Docker Tool.
<https://github.com/t3docs/docker-render-documentation>

### Contributions

When you have a PR, please run the following checks first.

* `composer test:all`
    * Requires a mysql-database, you can boot one with `docker-compose` from the `.Docker`-directory
* `composer cs-fix`
    * Ensures that coding standards are respected
* `composer analyse`
    * Will run PHPStan and do a static code analysis, this is not adjust completely in build yet, but please try to avoid adding new violations. ;)

#### Devbox

If you don't have a setup already, where you can do development, bugfixing etc. for the crawler, don't worry.

We have included a [ddev](https://www.ddev.com) devbox to help the development.

##### Prerequisites

* [DDEV](https://www.ddev.com)
* Docker

##### How to use the devbox?

```
$ git clone git@github.com:AOEpeople/crawler.git
$ cd .devbox
$ ddev start
```

And start working.

**INFO** 
xdebug is disable as default, to speed up the devbox when xdebug isn't needed.

This can be activated in `.devbox/.ddev/config.yaml` and by `ddev restart` afterwards.