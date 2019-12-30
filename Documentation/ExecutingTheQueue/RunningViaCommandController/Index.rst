Include ../../Includes.txt


Run via command controller
^^^^^^^^^^^^^^^^^^^^^^^^^^

Create queue
------------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:buildqueue [--page <page-id>] [--depth <depth>] [--conf <configurationKey1,configurationKey2,...>] [--number <number>] [--mode <exec|queue|url>]

Run queue
---------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:processqueue [--amount <pages to crawl>] [--sleeptime <milliseconds>] [--sleepafter <seconds>]

Flush queue
-----------

::

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:flushqueue [--mode <pending|finished|all>] [--page <id of top page>]