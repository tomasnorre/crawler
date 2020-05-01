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


Cache warm up
-------------

To have a website that is fast for the end-user is essential, therefor having a warm cache even before the
first user hits the newly deployed website, will be beneficial, so how could one achieve this?

The crawler have some command line tools (herafter cli tools) that can be used, during deployments. The cli tools is
implemented with the `symfony/console` which have been standard in TYPO3 for a while.

There are 3 commands that can be of you benefit during deployments.

* `vendor/bin/typo3 crawler:flushQueue`
* `vendor/to/bin/typo3 crawler:buildQueue`
* `vendor/to/bin/typo3 crawler:processQueue`

You can see more on which parameters they take in :ref:`command-controller`, this example will provide suggestion on how you can
set it up, and you can adjust with additional parameters if you like.

First we need a `crawler configuration` these are stored in the Database. You can add it via the backend, see :ref:`backend-configuration-record`.

It's is suggested to select the most important pages of the website and add them to a Crawler configuration called e.g. `deployment`

.. image:: /Images/backend_configuration_deployment.png

With this only pages added will be crawled when using this configuration. So how will we execute this from CLI during deployment?
I don't know which deployment tool you use, but it's not important as long as you can execute shell commands. What would you need to execute?

::

    # Done to make sure the crawler queue is empty, so that we will only crawl important pages.
    $ vendor/bin/typo3 crawler:flushQueue all

    # Now we want to fill the crawler queue,
    # This will start on page uid 1 with the deployment configuration and depth 99,
    # --mode exec crawles the pages instantly so we don't need a secondary process for that.
    $ vendor/bin/typo3 crawler:buildQueue 1 deployment --depth 99 --mode exec

    # Add the rest of the pages to crawler queue and have the processed with the scheduler
    # --mode queue is default, but it is  added for visibility,
    # we assume that you have a crawler configuration called default
    $ vendor/bin/typo3 crawler:buildQueue 1 default --depth 99 --mode queue


The last step will add the pages to the queue, and you would need a scheduler task setup to have them executed. Go to
the scheduler module and do following steps:

1. Add a new Scheduler Task
2. Select Frequency for the execution
3. Select the `Execute console commands`
4. Go to section `Schedulable Command. Save and reopen to define command arguments` at the bottom.
5. Select `Crawler:processQueue` (press save)
6. Select the options you want to execute the queue with, it's important to check the checkboxes and not only fill in the values.

.. image:: /Images/backend_scheduler_processqueue.png

With there steps you will have a website that is faster byt the first visit after a deployment, and the rest of the website
is crawled automatically shortly after.

`#HappyCrawling`

