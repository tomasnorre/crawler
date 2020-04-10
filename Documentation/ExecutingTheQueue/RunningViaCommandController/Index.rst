.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)

Run via command controller
^^^^^^^^^^^^^^^^^^^^^^^^^^

Create queue
------------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:buildqueue <page-id> <configurationKey1,configurationKey2,...> [--depth <depth>] [--number <number>] [--mode <exec|queue|url>]

Run queue
---------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:processqueue [--amount <pages to crawl>] [--sleeptime <milliseconds>] [--sleepafter <seconds>]

Flush queue
-----------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:flushqueue <pending|finished|all> [--page <id of top page>]
