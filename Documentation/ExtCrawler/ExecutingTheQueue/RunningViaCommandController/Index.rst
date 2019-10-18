Include ../../Includes.txt


Run via command controller
^^^^^^^^^^^^^^^^^^^^^^^^^^

Create queue
------------

..code

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:buildqueue [--startpage <page-id>] [--depth <depth>] [--conf <configurationKey1,configurationKey2,...>] [--number <number>] [--mode <exec|queue|url>]

Run queue
---------

..code

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:crawlqueue [--amount <pages to crawl>] [--sleeptime <milliseconds>] [--sleepafter <seconds>]

Flush queue
-----------

..code

   # replace vendor/bin/typo3 with your own cli runner
   $ vendor/bin/typo3 crawler:flushqueue [--mode <pending|finished|all>] [--page <id of top page>]