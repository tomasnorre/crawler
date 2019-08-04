

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


Executing the queue
-------------------

The idea of the queue is that a large number of tasks can be submitted
to the queue and performed over longer time. This could be interesting
for several reasons;

- To spread server load over time.

- To time the requests for nightly processing

- And simply to avoid “max\_execution\_time” of PHP to limit processing
  to 30 seconds !


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   ExecutingQueueWithCron-job/Index
   RunViaBackend/Index
   AddingEntriesToTheQueueByContextMenue/Index
   BuildingAndExecutingQueueRightAway(fromCli)/Index

