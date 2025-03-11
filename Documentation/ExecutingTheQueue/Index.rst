..  include:: /Includes.rst.txt

..  _executing-the-queue-label:

===================
Executing the queue
===================

The idea of the queue is that a large number of tasks can be submitted
to the queue and performed over longer time. This could be interesting
for several reasons;

-   To spread server load over time.

-   To time the requests for nightly processing.

-   And simply to avoid `max_execution_time` of PHP to limit processing
    to 30 seconds!


..  toctree::
    :maxdepth: 5
    :titlesonly:
    :glob:

    RunningViaCommandController/Index
    ExecutingQueueWithCron-job/Index
    RunViaBackend/Index
    BuildingAndExecutingQueueRightAway(fromCli)/Index
