.. include:: /Includes.txt

============================
Automatic add pages to Queue
============================

Since 9.1.0

Edit Pages
----------

With this feature, you will automatically add pages to the crawler queue
when you are editing content on the page, unless it's within a workspace, then
it will not be added to the queue before it's published.

This functionality gives you the advantages that you would not need to keep track
of which pages you have edited, it will automatically be handle on next crawler
process task, see :ref:`executing-the-queue-label`. This ensure that
your cache or e.g. Search Index is always up to date and the end-users will see
the most current content as soon as possible.

Clear Page Single Cache
-----------------------

As the edit and clear page cache function is using the same dataHandler hooks,
we have an additional feature for free. When you clear the page cache for a specific
page then it will also be added automatically to the crawler queue. Again this will
be processed during the next crawler process.

.. figure:: /Images/backend_clear_cache.png
   :alt: Clearing the page cache

   Clearing the page cache

.. figure:: /Images/backend_clear_cache_queue.png
   :alt: Page is added to the crawler queue

   Page is added to the crawler queue

Swapping live and workspace record
----------------------------------

If you have workspaces installed, you will depending on you configuration of the workspace,
have the posibility to :guilabel`Swap live and workspace versions of record`.
This functionality do as it says, switch the current workspace version of the
record with the one in the live-workspace. When you use this functions, the
changes pages, just published, will be added to the crawler queue as well.
