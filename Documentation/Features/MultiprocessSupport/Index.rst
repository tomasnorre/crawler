﻿.. include:: /Includes.rst.txt

=====================
Multi process support
=====================

If you want to optimize the crawling process for speed (instead of low
server stress), maybe because the machine is a dedicated staging
machine you should experiment with the new multi process features.

In the extension settings you can set how many processes are allowed to
run at the same time, how many queue entries a process should grab and
how long a process is allowed to run. Then run one (or even more)
crawling processes per minute. You'll be able to speed up the crawler quite a lot.

But choose your settings carefully as it puts loads on the server.

.. figure:: /Images/crawler_settings_processLimit.png
   :alt: Backend configuration: Processing

   Backend configuration: Processing
