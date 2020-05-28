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
.. _executing-the-queue-cli-label:

Building and Executing queue right away (from cli)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

An alternative mode is to automatically build and execute the queue
from the command line in one process. This doesn't allow scheduling of
task processing and consumes as much CPU as it can. On the other hand
the job is done right away. In this case the queue is both built and
executed right away.

The script to use is this:

::

   [pathToYourTYPO3Installation-composer-bin-dir]/typo3cms crawler:buildQueue <startPageUid> <configurationKeys>

If you run it you will see a list of options which explains usage.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         <startPageUid>

   Data type
         integer

   Description
         Page Id of the page to use as starting point for crawling.

   Default
         n/a

.. container:: table-row

   Property
         <configurationKeys>

   Data type
         string

   Description
         Configurationkey:

         Comma separated list of your crawler configurations. If you use the
         crawler configuration records you have to use the "name" if you're
         still using the old TypoScript based configuration you have to use the
         configuration key which is also a string.

         **Examples:**

         ::

            re-crawl-pages,re-crawl-news

   Default
         n/a


.. container:: table-row

   Property
         --number <number>

   Data type
         integer

   Description
         Specifies how many items are put in the queue per minute. Only valid
         for output mode "queue".

   Default
         n/a


.. container:: table-row

   Property
         --mode <mode>

   Data type
         string

   Description
         Output mode: "url", "exec", "queue"

         \- url : Will list URLs which wget could use as input.

         \- queue: Will put entries in queue table.

         \- exec: Will execute all entries right away!

   Default
         Queue


.. container:: table-row

   Property
         --depth <depth>

   Data type
         integer

   Description
         Tree depth, 0-99.

         How many levels under the 'page\_id' to include.

   Default
         n/a


.. ###### END~OF~TABLE ######

Example
-------

We want to crawl pages under the page "Content Examples" (uid=6) and 2 levels down, with the default crawler configuration.

This is done like this in the backend.

.. image:: /Images/backend_startcrawling.png

To do the same with the CLI script you run this:

::

   [pathToYourTYPO3Installation-composer-bin-dir]/typo3 crawler:buildQueue 6 default --depth 2

And this is the output:

::

    38 entries found for processing. (Use "mode" to decide action):

    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/overview
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/rich-text
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/headers
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/bullet-list
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/text-with-teaser
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/text-and-icon
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/text-in-columns
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/list-group
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/panel
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/table
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/text/quote
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/media/audio
    [10-04-20 10:35] https://crawler-devbox.ddev.site/content-examples/media/text-and-images
    ...
    [10-04-20 10:36] https://crawler-devbox.ddev.site/content-examples/and-more/frames


At this point you have three options for "action":

- Commit the URLs to the queue and let the cron script take care of it
  over time. In this case there is an option for setting the amount of
  tasks per minute if you wish to change it from the default 30. This is
  useful if you would like to submit a job to the cron script based
  crawler everyday.

  - Add "--mode queue"
  - This is also the **default** setting, so unless you want it to be explicit visible, you don't need to add it.

- List full URLs for use with wget or similar. Corresponds to pressing
  the "Download URLs" button in the backend module.

  - Add "--mode url"

::

    $ bin/typo3 crawler:buildQueue 6 default --depth 2 --mode url
    https://crawler-devbox.ddev.site/content-examples/overview
    https://crawler-devbox.ddev.site/content-examples/text/rich-text
    https://crawler-devbox.ddev.site/content-examples/text/headers
    https://crawler-devbox.ddev.site/content-examples/text/bullet-list
    https://crawler-devbox.ddev.site/content-examples/text/text-with-teaser
    https://crawler-devbox.ddev.site/content-examples/text/text-and-icon
    https://crawler-devbox.ddev.site/content-examples/text/text-in-columns
    https://crawler-devbox.ddev.site/content-examples/text/list-group
    https://crawler-devbox.ddev.site/content-examples/text/panel
    ...

- Commit and execute the queue right away. This will still put the jobs
  into the queue but execute them immediately. If server load is no
  issue to you and if you are in a hurry this is the way to go! It also
  feels much more like the "command-line-way" of things. And the status
  output is more immediate than in the queue.

  - Add "--mode exec"

::

    $ bin/typo3 crawler:buildQueue 6 default --depth 2 --mode exec
    https://crawler-devbox.ddev.site/content-examples/overview
    https://crawler-devbox.ddev.site/content-examples/text/rich-text
    https://crawler-devbox.ddev.site/content-examples/text/headers
    https://crawler-devbox.ddev.site/content-examples/text/bullet-list
    https://crawler-devbox.ddev.site/content-examples/text/text-with-teaser
    https://crawler-devbox.ddev.site/content-examples/text/text-and-icon
    https://crawler-devbox.ddev.site/content-examples/text/text-in-columns
    https://crawler-devbox.ddev.site/content-examples/text/list-group
    https://crawler-devbox.ddev.site/content-examples/text/panel
    ...
    Processing

    https://crawler-devbox.ddev.site/content-examples/overview () =>

    OK:
            User Groups:

    https://crawler-devbox.ddev.site/content-examples/text/rich-text () =>

    OK:
            User Groups:

    https://crawler-devbox.ddev.site/content-examples/text/headers () =>

    OK:
            User Groups:

    https://crawler-devbox.ddev.site/content-examples/text/bullet-list () =>

    OK:
            User Groups:
    ...

