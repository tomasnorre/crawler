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


Run via backend
^^^^^^^^^^^^^^^

To process the queue you must either set up a cron-job on your server
or use the backend to execute the queue:

.. image:: /Images/backend_startnewprocess.png

You can also (re-)crawl singly urls manually from within the Crawler
log view in the info module:

.. image:: /Images/backend_recrawl.png

