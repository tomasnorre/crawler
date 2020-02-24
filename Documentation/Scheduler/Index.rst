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


Scheduler
---------


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:


As seen in :ref:`executing-the-queue-label` you can execute the queue in multiple ways, but it's no fun doing that manually all the time.

With the Crawler you have the possibility to add Scheduler Tasks to be executed on a give time.
The Crawler commands are implemented with the Symfony Console, and therefore they can be configured with the Core supported `Execute console commands (scheduler)` task.

So how to setup crawler scheduler tasks.

1. Add a new Scheduler Task
2. Select Frequency for the execution
3. Select the `Execute console commands`
4. Go to section `Schedulable Command. Save and reopen to define command arguments` at the bottom.
5. Select e.g. `Crawler:buildQueue` (press save)
6. Select the options you want to execute the queue with, it's important to check the checkboxes and not only fill in the values.

Now you can save and exit, and you scheduler tasks will be running as configured.

The configured scheduler will look like this:

.. image:: /Images/backend_scheduler_record.png

And after save and exit, you can see what command is executed, it would be the same parameters, you can use when running from cli, see :ref:`executing-the-queue-cli-label`

.. image:: /Images/backend_scheduler_overview.png



