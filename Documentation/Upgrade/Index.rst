..  include:: /Includes.rst.txt

..  _upgrade:

=======
Upgrade
=======

..  _upgrade-message-bus:

Message bus instead of the Crawler process handling
===================================================

The Crawler no longer processes its queue with its own processes. Every URL
added to the queue is dispatched as a
`AOE\Crawler\Queue\Message\CrawlUrlMessage` to the
`TYPO3 message bus <https://docs.typo3.org/permalink/t3coreapi:message-bus>`__
and processed by a worker:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low

See :ref:`with-crown` for how to keep the worker running.

The queue table `tx_crawler_queue` is kept: it holds the URLs to crawl and the
crawl results shown in the :guilabel:`Crawler log`.

Migration
---------

#.  Run the database analyzer to remove the table `tx_crawler_process` and the
    columns `process_id`, `process_id_completed` and `process_scheduled` of
    `tx_crawler_queue`.

#.  Replace all `crawler:processQueue` cron-jobs and scheduler tasks with
    `messenger:consume crawler_high crawler crawler_low`.

#.  Pending queue entries created before the upgrade have no message on the
    message bus. Flush them with `vendor/bin/typo3 crawler:flushQueue pending`
    and build the queue again.

Removed functionality
---------------------

*   The command `crawler:processQueue`. Use `messenger:consume crawler_high crawler crawler_low`.

*   The :guilabel:`Process` view of the backend module and the AJAX route
    `crawler_process_status`. Workers are managed outside of TYPO3, e.g. by
    systemd.

*   The table `tx_crawler_process` and the classes
    `AOE\Crawler\Crawler`,
    `AOE\Crawler\Domain\Model\Process`,
    `AOE\Crawler\Domain\Model\ProcessCollection`,
    `AOE\Crawler\Domain\Repository\ProcessRepository`,
    `AOE\Crawler\Service\ProcessService`,
    `AOE\Crawler\Service\BackendModuleLinkService`,
    `AOE\Crawler\Process\*`,
    `AOE\Crawler\Helper\Sleeper\*`,
    `AOE\Crawler\Hooks\ProcessCleanUpHook`,
    `AOE\Crawler\Hooks\CrawlerHookInterface`,
    `AOE\Crawler\Utility\HookUtility` and
    `AOE\Crawler\EventListener\AfterQueueItemAddedEventListener`.

*   The unused classes `AOE\Crawler\Domain\Model\Configuration`,
    `AOE\Crawler\Domain\Model\Queue`, `AOE\Crawler\Value\CrawlAction`,
    `AOE\Crawler\Exception\CrawlerObjectException` and
    `AOE\Crawler\Exception\TimeStampException`, and the deprecated method
    `CrawlerController::drawURLs_PIfilter()`, use
    `ProcessInstructionService::isAllowed()` instead.

*   The hooks `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['cli_hooks']`
    and `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['refresh_hooks']`.

*   The methods `CrawlerController::readUrl()` and
    `CrawlerController::readUrlFromArray()`, as well as the property
    `CrawlerController::$processID`. Dispatch a
    `AOE\Crawler\Queue\Message\CrawlUrlMessage` instead.

*   The extension settings `sleepTime`, `sleepAfterFinish`, `countInARun`,
    `processLimit`, `processMaxRunTime`, `purgeQueueDays`, `processDebug` and
    `processVerbose`. Use the options of `messenger:consume` (`--limit`,
    `--time-limit`, `--memory-limit`) and run several workers to process
    entries in parallel. Old processed entries are still removed by
    `cleanUpOldQueueEntries`.

Changed functionality
---------------------

*   :ref:`priority-crawling` is done with three transports, `crawler_high`,
    `crawler` and `crawler_low`, instead of sorting the queue. A worker must
    consume them in that order: `messenger:consume crawler_high crawler crawler_low`.

*   The result of a crawl is stored by the message handler, after the event
    `AOE\Crawler\Event\AfterQueueItemAddedEvent` has been dispatched. Listeners
    can still modify the stored fields with `setFieldArray()`.

*   Re-crawling an entry from the :guilabel:`Crawler log` dispatches a message,
    the result is visible once the worker has processed it.

..  _upgrade-queue-callbacks:

Queue callbacks implement an interface
======================================

Callbacks in the queue implement `AOE\Crawler\Queue\QueueCallbackInterface`
instead of a `crawler_execute()` method, and no longer receive the
`CrawlerController`. Callbacks that are services get their dependencies by
constructor injection instead.

Migration
---------

#.  Implement `QueueCallbackInterface` and rename `crawler_execute()` to
    `execute()`:

    ..  code-block:: diff

        -class MyCallback
        +final class MyCallback implements \AOE\Crawler\Queue\QueueCallbackInterface
         {
        -    public function crawler_execute(array $parameters, CrawlerController $crawlerController)
        +    public function execute(array $parameters): mixed

#.  Add queue entries with `QueueEntryWriter::addCallback()`, the replacement
    of the removed `CrawlerController::addQueueEntry_callBack()`.

See :ref:`queue-callbacks`.

..  _upgrade-language-packs:

Translations are delivered as language packs
============================================

The extension no longer ships translated XLIFF files, only the English labels.
The translations are maintained on
`Crowdin <https://crowdin.com/project/typo3-extension-crawler>`__ and
delivered by the TYPO3 translation server as language packs, like the
translations of the TYPO3 Core.

Without language packs, the backend module and the Crawler labels are shown
in English. Fetch or update the language packs after installing or updating
the extension, either in the backend module
:guilabel:`System > Maintenance > Manage Language Packs`, or on the command
line:

..  code-block:: bash

    vendor/bin/typo3 language:update

See `Managing translations <https://docs.typo3.org/permalink/t3coreapi:managing-translating>`__
for details.

Missing or wrong translations can be fixed on Crowdin, they are part of the
next language pack.
