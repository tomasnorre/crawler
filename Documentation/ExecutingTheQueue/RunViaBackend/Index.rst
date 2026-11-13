..  include:: /Includes.rst.txt

..  _run-backend:

===============
Run via backend
===============

Pages added to the queue via the :guilabel:`Start Crawling` view of the
:guilabel:`Site Crawler` module are processed by the worker, see
:ref:`with-crown`.

You can also (re-)crawl single URLs manually from within the :guilabel:`Crawler
log` view. The URL is handed to the worker again and the result is shown in the
log once it has been crawled:

..  figure:: /Images/backend_crawlerlog_recrawl.png
    :alt: Crawl single URLs via backend

    Crawl single URLs via backend
