# Changelog TYPO3 Crawler

## Crawler 9.2.3-dev

###Added

###Changed
* Default fields in `tx_crawler_configuration` are remove from `ext_table.sql` as they are obsolete, will be added from TCA

###Deprecated
#### Functions & Properties

###Removed
#### Functions & Properties

###Fixed
* Removed required for `processing_instruction_filter` in `tx_crawler_configuration`

###Security

## Crawler 9.2.2
Crawler 9.2.2 was released on January 26th, 2021

### Added
* AccessService::hasGroupAccess()

### Deprecated
#### Functions & Properties
* CrawlerController->CLI_releaseProcesses()
* CrawlerController->hasGroupAccess()

### Fixed
* buildQueue with `--mode exec` resulted in a 503
* Set content in `fetchUrlContents()` to false if null, to prevent serializing from failing

## Crawler 9.2.1
Crawler 9.2.1 was released on December 27th, 2020

### Changed
* Moved HTML from the LogRequestForm->drawLog_addRows() to Fluid-template
* Extended the Example for PageTS Configuration

### Deprecated
#### Functions & Properties
* SignalSlotUtility->emitSignal()

#### Classes
* SignalSlotUtility

### Fixed
* PageTS Crawler configurations (broken since 9.1.3)

## Crawler 9.2.0
Crawler 9.2.0 was released on December 22nd, 2020

### Added
* Add support for TYPO3 11.0

### Deprecated
#### Classes
* CrawlerApi

## Crawler 9.1.5
Crawler 9.1.5 was released on December 19th, 2020

### Added
* Error-Handling when Page 0 is used for crawler:buildQueue

### Changed
* Removed all references to cHash as it is not used anymore.

### Deprecated
#### Functions & Properties
* CrawlerController->getLogEntriesForPageId()
* CrawlerController->CLI_runHooks()
* QueueRepository->countAllByProcessId()
* QueueRepository->countUnprocessedItems()
* FlushQueueCommand --page option

### Fixed
* Typecasting port number in cli/bootstrap to have processQueue working with non-standard ports
* Flush entire queue works again
* Check if PageRow['uid'] is integer before handing it on to next function

## Crawler 9.1.4
Crawler 9.1.4 was released on November 27th, 2020

### Changed
* IndexedSearchCrawlerHook::class is marked as deprecated and will be removed when dropping support for TYPO3 9LTS and 10LTS

### Deprecated
#### Classes
* IndexedSearchCrawlerHook

#### Functions & Properties
* CrawlerController->getDuplicateRowsIfExist()

### Fixed
* Auto-loading for non-composer projects fixed

## Crawler 9.1.3
Crawler 9.1.3 was released on November 20nd, 2020

### Added
* QueueFilter to operate with Object instead of string/arrays

### Deprecated
#### Classes

#### Functions & Properties
* BackendModule->modMenu()
* CrawlerController->CLI_debug()
* CrawlerController->getAccessMode()
* CrawlerController->setAccessMode()
* CrawlerController->getDisabled()
* CrawlerController->setDisabled()
* CrawlerController->getProcessFilename()
* CrawlerController->setProcessFilename()
* CrawlerController->accessMode
* CrawlerController->processFilename

### Changed
* Updated Rector, PHPStan, ECS for better static code analysis
* BackendModule is splittet into smaller classes to improve readability and maintainability
* Crawler Class added, this can be used to check whether the crawler is enabled or disabled

### Fixed
* aoe/crawler/initialization (middleware) is moved to before typo3/cms-core/normalizedParams to have crawler being "last" in middleware chain

## Crawler 9.1.2
Crawler 9.1.2 was released on November 7th, 2020

### Added
* Progress bar to the `crawler:buildQueue` command output when using with `--mode exec`
* Improve documentation about proper crawler configuration for user with _Website Usergroups_

### Fixed
* Detailed process views is callable again
* Makes sure the QueueRepository is always set when needed in Domain/Model/Process
* Crawling with FE-Groups is correct initialized with both TYPO3 9 & 10

## Crawler 9.1.1
Crawler 9.1.1 was released on October 17th, 2020

### Added
* Documentation example for ext:news
* CrawlStrategyFactory to move logic out of the QueueExecutor

### Deprecated

#### Functions & Properties
* ProcessRepository->countActive()
* ProcessRepository->getLimitFromItemCountAndOffset()
* CrawlerController->getUrlFromPageAndQueryParameters()

### Changed
* UrlService->getUrlFromPageAndQueryParameters() moved from CrawlerController

### Fixed
* Frontend User initialization with UserGroups for crawling protected pages
* Making sure PageUid added with ExcludeString is kept as integers
* Instantiation of ProcessRepository and QueueRepository change to GeneralUtility::makeInstance
* Ensure that DataHandlerHook will not add pages to queue that does not exist

## Crawler 9.1.0
Crawler 9.1.0 was released on August 2nd, 2020

### Added
* Adds light red background color + icons to crawler log rows with errors
* Crawler processing by page priority
* Automatically adding pages being edited or a page caches is cleared to the crawler queue

## Crawler 9.0.3
Crawler 9.0.2 was released on July 20th, 2020

### Added
* Added more information into [CONTRIBUTING.md](CONTRIBUTING.md) about development using container

### Fixed
* Load crawler initialization before TSFE rendering preparation

## Crawler 9.0.2
Crawler 9.0.2 was released on July 13th, 2020

### Added
* Update specific doktypes for skipping page
* Configure commands in Services.yaml for TYPO3 v10
* Tests for BackendModule
* Add index for tx_crawler_queue.scheduled table field
* Support for Symfony console 5
* Add example for pageVeto hook in documentation

### Changed
* Group TCA in tabs and add descriptions to fields

### Fixed
* Handle empty response when fetching URL
* Set default value when procInstrFilter is not defined

## Crawler 9.0.1
Crawler 9.0.1 was released on May 11th, 2020

### Added
* Documentation on how to use the Crawler for cache warmup during deployments
* Add better error handling to getPhpBinary + More tests for getPhpBinary
* Add SonarCloud to Pipeline
* More tests for QueryRepository
* More tests for ProcessRepository

### Changed
* Switched locallang.xml to xlf for crowdin support
* Corrected typos in Documentation and signals
* Cleanup CrawlerController - functions moved to respected Repository
* Moved HTMl from BackendModule showLog to Fluid template
* Switched from serialized to json-data for Queue information

### Deprecated
#### Classes

#### Functions & Properties
* ProcessService->countInARun
* ProcessService->crawlerController
* ProcessService->processLimit
* ProcessService->queueRepository
* ProcessService->verbose
* CrawlerController->cleanUpOldQueueEntries()
* CrawlerController->flushQueue()
* CrawlerController->getLogEntriesForSetId()
* Process->getTimeForFirstItem()
* Process->getTimeForLastItem()
* ProcessService->multiProcess()
* ProcessService->reportItemStatus()
* ProcessService->startRequiredProcesses()

### Removed
#### Classes
* EventDispatcher
* EventObserverInterface

#### Functions & Properties
* Process->row
* Queue->row
* CrawlerApi->countEntriesInQueueForPageByScheduleTime()

### Fixed
* Changed result handling for Crawling in case of 500 errors on directRequests
* Set correct tableName for mountPoint queries
* Fallback to alturl is added to ensure crawling of pages including PDF files
* Get fe_user from $GLOBALS['TSFE'] instead of $request->getAttribute()
* Have getTimeForFirstItem() and getTimeForLastItem return 0 is no process found

## Crawler 9.0.0
Crawler 9.0.0 was released on April 14th, 2020

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
We don't have any changelogs between version 5.1.3 and 9.0.0, version 7 and 8 was never released, we jumped to 9.0.0

## Crawler 5.1.3
Crawler 5.1.3 was released on July 21st, 2017.

### Changes
    [RELEASE] Release of crawler 5.1.3
    [TASK] Added Cache to TypoScriptUtility

## Crawler 5.1.2
Crawler 5.1.2 was released on November 24th, 2016.

### Changes
    [RELEASE] Release of crawler 5.1.2
    [BUGFIX] Scheduler Tasks broken after upgrade to 5.1.1 (composer) (#154)
    [BUGFIX] Fixed incorrect class name in ClassAliasMap (#153)

## Crawler 5.1.1
Crawler 5.1.1 was released on November 21th, 2016.

### Changes
    [RELEASE] Release of crawler 5.1.1
    [BUGFIX] It's not possible to "add process" after upgrade to 5.1.0 (#145)
    [BUGFIX] #1442236317: MenuItem "" is not valid (More information) (#148)
    [BUGFIX] Not possible to add new scheduler tasks (not only Crawler tasks) after upgrade to 5.1.0 (#144)
    [BUGFIX] Scheduler Tasks broken after upgrade to 5.1.0 (#142)

## Crawler 5.1.0
Crawler 5.1.0 was released on November 15th, 2016.

### Changes
    [FEATURE] Remove assignment of queue items to process if process gets killed by cleanup hook
    [FEATURE] Adjust namespace of ProcessCleanupTask (#138)
    [CLEANUP] Streamline ext_emconf.php and ext_localconf.php
    [TASK] Convert locallang_db.xml to xlf Resolves #134
    [TASK] Auto detect TypoScript Template
    [TASK] Moving hooks to Classes/Hooks and create utilities for Hooks and Scheduler tasks
    [TASK] Move "scheduler"-classes into "Classes/Tasks"
    [TASK] Set version to 5.0.10-dev


## Crawler 5.0.9
Crawler 5.0.9 was released on September 16th, 2016.

### Changes
    [RELEASE] Release of crawler 5.0.9
    [BUGFIX] Switch to PHP 5.3 compatible array syntax
    [TASK] Set version to 5.0.9-dev

## Crawler 5.0.8
Crawler 5.0.8 was released on September 16th, 2016.

### Changes
    [RELEASE] Release of crawler 5.0.8
    [FEATURE] Emit signals before/after a queue item is processed
    [TASK] Update README.md
    [TASK] Add scheduler task for process cleanup hook
    [TASK] Set version to 5.0.8-dev

## Crawler 5.0.7
Crawler 5.0.7 was released on September 6th, 2016.

### Changes
    [RELEASE] Release of crawler 5.0.7
    [FEATURE] Adjust autoload configuration for TYPO3 7+ (#117)
    [TASK] Update exception for "no typoscript template found" (#114)
    [TASK] Remove deprecated API call (#112)
    [TASK] Adjust composer psr-4 autoloading - fixes issue #115
    [TASK] Set version to 5.0.7-dev

## Crawler 5.0.6
Crawler 5.0.6 was released on August 22nd, 2016.

### Changes
    [RELEASE] Release of crawler 5.0.6
    Revert "Merge pull request #104 from AOEpeople/feature/remove-curl"
    #107 Fixes deprecated notices (#108)
    [TASK] Set version to 5.0.6-dev

## Crawler 5.0.5
Crawler 5.0.5 was released on August 15th, 2016.

### Changes
    [RELEASE] Release of crawler 5.0.5
    [FEATURE] Remove option to request pages by HTTP/HTTPS
    [FEATURE] Remove option to follow redirects
    [FEATURE] Introduce backend utility class to clean up ext_tables.php
    [TASK] Remove obsolete validator class (#101)
    [BUGFIX] Remove dependency for workspaces (#97)
    replace deprecated function call in TCA file, add renderType for select fields (#96)
    [BUGFIX] Crawler context menu throws fatal error
    [TASK] Deleting old tx_crawler_processes entries ends up in slow query
    [BUGFIX] Fix broken displayCond
    Revert "[TASK] Re-enable Workspace after Core Issue #70052 fixed"
    [TASK] Missing "hide"/"disable" field in the configuration record form
    [TASK] config.absRefPrefix not respected by tx_crawler_lib::getFrontendBasePath()
    [TASK] Set version to 5.0.5-dev
    [TASK] Remove "Add Process"-button when no more queue-item can be assigned

## Crawler 5.0.4
Crawler 5.0.4 was released on March 18th, 2016.

### Changes
    [RELEASE] Release of crawler 5.0.4
    [TASK] Replace deprecated parameter
    Revert "[TASK] config.absRefPrefix not respected by tx_crawler_lib::getFrontendBasePath()"
    [TASK] Re-enable Workspace after Core Issue #70052 fixed
    Improve configuration record documentation
    [TASK] config.absRefPrefix not respected by tx_crawler_lib::getFrontendBasePath()
    [TASK] Add Travis Config for both TYPO3 6 and 7LTS
    [TASK] Remove deprecated call of GeneralUtility::loadTCA()
    [TASK] Set version to 5.0.4-dev

## Crawler 5.0.3
Crawler 5.0.3 was released on March 10th, 2016.

### Changes
    [RELEASE] Release of crawler 5.0.3
    [TASK] Split Unit and Functional tests
    Update .travis.yml
    Renamed tests -> Tests
    [TASK] Update Travis script to be prepared for TYPO3 7 LTS compatibility update later
    [TASK] Update Documentation
    [TASK] #86620 Add refresh hooks so that the refresh button also removes orphan and old processes.
    [BUGFIX] Links on refresh icon/queue id do not work in TYPO3 7.6
    Set version to 5.0.2-dev
    [FIX] Fixed to ensure traversable else return


## Sorry
We don't have any changes logs later than this.
