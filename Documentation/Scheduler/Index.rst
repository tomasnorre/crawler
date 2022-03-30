.. include:: /Includes.rst.txt

=========
Scheduler
=========


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:


As seen in :ref:`executing-the-queue-label` you can execute the queue in
multiple ways, but it's no fun doing that manually all the time.

With the Crawler you have the possibility to add Scheduler Tasks to be executed
on a give time. The Crawler commands are implemented with the Symfony Console,
and therefore they can be configured with the Core supported
`Execute console commands (scheduler)` task.

So how to setup crawler scheduler tasks:

1. Add a new Scheduler Task
2. Select the class :guilabel:`Execute console commands`
3. Select :guilabel:`Frequency` for the execution
4. Go to section :guilabel:`Schedulable Command. Save and reopen to define
   command arguments` at the bottom.
5. Select e.g. :guilabel:`crawler:buildQueue` (press save)
6. Select the options you want to execute the queue with, it's important to
   check the checkboxes and not only fill in the values.

Now you can save and close, and your scheduler tasks will be running as
configured.

The configured task will look like this:

.. figure:: /Images/backend_scheduler_record.png
   :alt: Task configuration for building the queue

   Task configuration for building the queue

And after save and close, you can see what command is executed, it would be
the same parameters, you can use when running from cli,
see :ref:`executing-the-queue-cli-label`

.. figure:: /Images/backend_scheduler_overview.png
   :alt: Task in the scheduled tasks overview

   Task in the scheduled tasks overview
