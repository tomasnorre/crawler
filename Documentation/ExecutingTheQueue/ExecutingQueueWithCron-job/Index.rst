..  include:: /Includes.rst.txt

..  _with-crown:

======================
Running the worker
======================

Since version 14 the Crawler uses the
`TYPO3 message bus <https://docs.typo3.org/permalink/t3coreapi:message-bus>`__
instead of its own queue processing. Every URL added to the queue results in a
`AOE\Crawler\Queue\Message\CrawlUrlMessage`, which is sent to one of three
Doctrine transports (stored in the table `sys_messenger_messages`), depending on
the priority of the page, see :ref:`priority-crawling`:

*   `crawler_high`
*   `crawler`
*   `crawler_low`

The messages are processed by a worker. The order of the transports is
important, the worker always processes the messages of the first transport
before it looks at the next one:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low

Several workers can run at the same time, the transports make sure that every
message is only processed once.

Keep the worker running with systemd (recommended)
==================================================

A worker stops when one of its limits is reached. Let a process manager like
systemd restart it:

..  code-block:: ini
    :caption: /etc/systemd/system/typo3-crawler-worker.service

    [Unit]
    Description=TYPO3 Crawler worker

    [Service]
    ExecStart=/var/www/html/vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low --time-limit=3600 --memory-limit=256M
    Restart=always
    User=www-data

    [Install]
    WantedBy=multi-user.target

Run the worker with a cron-job
==============================

If you can't run a long-running process, start the worker from a cron-job and
let it stop before the next run starts:

..  code-block:: plaintext

   */5 * * * * vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low --time-limit=270 > /dev/null

The same command can also be configured as a scheduler task, see
:ref:`scheduler`.

Routing
=======

The Crawler routes its messages to the `crawler` transport in its
:file:`ext_localconf.php`. As long as this routing is used, the messages are
sent to the transport matching their priority. The routing can be changed in
:file:`config/system/settings.php` or :file:`config/system/additional.php`, e.g.
to crawl synchronously while debugging:

..  code-block:: php
    :caption: config/system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'][\AOE\Crawler\Queue\Message\CrawlUrlMessage::class] = 'default';

With the `default` (synchronous) transport, every URL is crawled at the moment
it is added to the queue, which is not recommended for larger sites. When the
routing is changed, the priority of the pages is not taken into account.
