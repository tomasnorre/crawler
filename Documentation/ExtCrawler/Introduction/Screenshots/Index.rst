.. include:: Images.txt

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


Screenshots
^^^^^^^^^^^

Has a backend module which displays the queue and log and allows
execution and status check of the “cronscript” from the backend for
testing purposes.

Here is the CLI (Command Line Interface = shell script = cron script)
status display:

.. image:: /Images/backend_processlist.png

Here is the crawler queue (before processing) / log (after processing)

.. image:: /Images/backend_crawlerlog.png

Here is the interface for submitting a batch of URLs to be
crawled. The parameter combinations are programmeble through Page
Tsconfig or configuration records.

.. image:: /Images/backend_pendingurls.png

