.. include:: /Includes.txt

.. _command-controller:

==========================
Run via command controller
==========================

Create queue
------------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:buildQueue <page-id> <configurationKey1,configurationKey2,...> [--depth <depth>] [--number <number>] [--mode <exec|queue|url>]

Run queue
---------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:processQueue [--amount <pages to crawl>] [--sleeptime <milliseconds>] [--sleepafter <seconds>]

Flush queue
-----------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:flushQueue <pending|finished|all>
