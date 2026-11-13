..  include:: /Includes.rst.txt

..  _command-controller:

==========================
Run via command controller
==========================

..  _command-controller-buildqueue:

Create queue
------------

..  code-block:: bash
    :caption: replace vendor/bin/typo3 with your own cli runner

    $ vendor/bin/typo3 crawler:buildQueue <page-id> <configurationKey1,configurationKey2,...> [--depth <depth>] [--number <number>] [--mode <exec|queue|url>]

..  _command-controller-processqueue:

Run queue
---------

The queue entries are processed by the TYPO3 message bus. Start a worker that
consumes the crawler transports, ordered by priority:

..  code-block:: bash
    :caption: replace vendor/bin/typo3 with your own cli runner

    $ vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low [--limit <messages>] [--time-limit <seconds>] [--memory-limit <memory>]

Without any limit the worker keeps running and waits for new queue entries. See
:ref:`with-crown` on how to keep the worker running.

..  _command-controller-flushqueue:

Flush queue
-----------

..  code-block:: bash
    :caption: replace vendor/bin/typo3 with your own cli runner

    $ vendor/bin/typo3 crawler:flushQueue <pending|finished|all>
