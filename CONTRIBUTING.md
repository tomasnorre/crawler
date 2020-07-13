### Contributing

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

```shell script
$ git clone git@github.com:AOEpeople/crawler.git
$ cd .devbox
$ ddev start
```

Username/password: `admin`/`password`

And start working.

**INFO**
xdebug is disable as default, to speed up the devbox when xdebug isn't needed.

This can be activated in `.devbox/.ddev/config.yaml` and by `ddev restart` afterwards.

#### Running tests without local development environment
If you don't have `php` and/or `composer` installed on your host machine,
you can run the test from withing the `ddev` docker container.

Do do that go into the `.devbox` folder an run `ddev ssh`.
From there you need to switch folder into `/public/typo3conf/ext/crawler`
and run `composer` commands from there (see above).
