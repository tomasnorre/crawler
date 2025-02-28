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

..  code-block:: bash
    :caption: replace vendor/bin/typo3 with your own cli runner

   $ vendor/bin/typo3 crawler:processQueue [--amount <pages to crawl>] [--sleeptime <milliseconds>] [--sleepafter <seconds>]

..  _command-controller-flushqueue:

Flush queue
-----------

..  code-block:: bash
    :caption: replace vendor/bin/typo3 with your own cli runner

    $ vendor/bin/typo3 crawler:flushQueue <pending|finished|all>
