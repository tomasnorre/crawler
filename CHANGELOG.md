# Changelog TYPO3 Crawler

## Version 9.0.1-dev
Released:

### Added
* Add better error handling to getPhpBinary + More tests for getPhpBinary
* Add SonarCloud to Pipeline
* More tests for QueryRepository
* More tests for ProcessRepository

### Changed
* Switched locallang.xml to xlf for crowdin support
* Corrected typos in Documentation and signals
* Cleanup CrawlerController - functions moved to respected Repository

### Deprecated
#### Classes

#### Functions & Properties
* ProcessService->queueRepository
* ProcessService->crawlerController
* ProcessService->countInARun
* ProcessService->processLimit
* ProcessService->verbose
* ProcessService->multiProcess()
* ProcessService->reportItemStatus()
* ProcessService->startRequiredProcesses()

### Removed
* ClassAliasMap and LegacyClassForIde as they are obsolete

### Fixed
* Changed result handling for Crawling in case of 500 errors on directRequests
* Set correct tableName for mountPoint queries

## Version 9.0.0
Released: 2020-04-14

### Added

* Support for TYPO3 9.5.14+
* Support for TYPO3 10LTS
* Execution Strategy classes [@bmack](https://github.com/bmack)
* Middleware implementation for Crawler and FE User Authentication [@ichhabrecht](https://github.com/ichhabrecht) [@bmack](https://github.com/bmack)
* GuzzleHttp as http client [@bmack](https://github.com/bmack)
* symfony/console for CommandController for CLI and Scheduler Tasks
* Backend Module uses Fluid for templating [@bmack](https://github.com/bmack)
* Add worker support (e.g. for indexed_search) and change procInstruction registration [@cdro](https://github.com/cdro)
* `_RECURSIVE` option to the subpartParams Configuration of the lookup `_TABLE` instructions [@ vzz3](https://github.com/vzz3)
* Rector to CI Pipeline [@TomasVotruba](https://github.com/TomasVotruba)
* PHPStan to CI Pipeline [@TomasVotruba](https://github.com/TomasVotruba)
* Behaviour Driven Tests to CI Pipeline
* Depency for typo3/cms-info
* Code of Conduct
* DDEV Devbox

### Changed
* Implemented Doctrine for Database handling [@cdro](https://github.com/cdro)
* Migrated FE hooks to PSR-15 middleware [@bmack](https://github.com/bmack)
* Switched to Site Handling instead of sys_domain [@bmack](https://github.com/bmack)
* Use TYPO3 Logging API for CrawlerController [@bmack](https://github.com/bmack)
* Switched from ClickMenu to ContextMenu API [@bmack](https://github.com/bmack)
* Migrate access of 'extConf' to TYPO3 9 API [tstahn](https://github.com/tstahn)
* Updated Extension Icons [@bmack](https://github.com/bmack)
* Documentation is updated
* From EXT:lang to EXT:core for V10 compatibility

### Removed
* Support for TYPO3 8
* Support for TYPO3 7
* PHP support for `<7.2` [@TomasVotruba](https://github.com/TomasVotruba)
* Remove root_template_pid option [@bmack](https://github.com/bmack)
* Dependency for `helhum/typo3-console`
* RealURL support
* "legacy" Scheduler Tasks
* nc_staticfile_cache hook not needed for TYPO3 9 LTS+

##### Classes
* AuthenticationService [@bmack](https://github.com/bmack)
* CommandLineController

##### Functions
* BackendModule->loadExtensionSettings()
* CrawlerApi->isPageInQueue()
* CrawlerApi->isPageInQueueTimed()
* CrawlerApi->getUnprocessedItems()
* CrawlerApi->countUnprocessedItems()
* CrawlerApi->removeQueueEntrie()
* CrawlerController->CLI_deleteProcessesMarkedDeleted()

## Sorry !!!
Unfortunately we haven't done any changelog before version 9.0.0, we are sorry for that.
