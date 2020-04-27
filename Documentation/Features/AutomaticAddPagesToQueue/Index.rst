

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


Automatic add pages to Queue
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Clear Cache
~~~~~~~~~~~

In version 9.1.0 the feature was added, that when you clear cache for a given page, it will automatically be added to the crawler queue.

.. image:: /Images/backend_clear_cache.png
.. image:: /Images/backend_clear_cache_queue.png

This will then be executed by the next crawler run.
