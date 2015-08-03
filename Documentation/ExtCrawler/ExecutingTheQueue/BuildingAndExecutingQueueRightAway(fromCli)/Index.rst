.. include:: Images.txt

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


Building and Executing queue right away (from cli)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

An alternative mode is to automatically build and execute the queue
from the command line in one process. This doesn't allow scheduling of
task processing and consumes as much CPU as it can. On the other hand
the job is done right away. In this case the queue is both built and
executed right away.

The script to use is this:

::

   [pathToYourTYPO3Installation]/typo3/cli_dispatch.phpsh crawler_im

If you run it you will see a list of options which explains usage.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:

   Data type
         Data type:

   Description
         Description:

   Default
         Default:


.. container:: table-row

   Property
         -conf configurationKeys

   Data type
         string

   Description
         Configurationkey:

         Commaseperated list of your crawler configurations. If you use the
         crwaler configuration records you have to use the “title” if your
         still using the old TypoScript based configuration you have to use the
         configuration key which is also a string.

         **Examples:**

         ::

            -conf re-crawle-pages,re-crawle-news

   Default
         n/a


.. container:: table-row

   Property
         -n number

   Data type
         integer

   Description
         Specifies how many items are put in the queue per minute. Only valid
         for output mode "queue".

   Default
         n/a


.. container:: table-row

   Property
         -o mode

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
         -d depth

   Data type
         integer

   Description
         Tree depth, 0-99.

         How many levels under the 'page\_id' to include.

   Default
         n/a


.. ###### END~OF~TABLE ######

Basically you must pass options similar to those you would otherwise
select using the Site Crawler when you set up a crawler job (“Start
Crawling”). Here is an example:

.. image:: /Images/backend_startcrawling.png

We want to publish pages under the page “ID=3 (“Contact” page
selected) and 1 level down (“1 level” selected) to static files
(Processing Instruction “Publish static [tx\_staticpub\_publish]”
selected). Four URLs are generated based on the configuration (see
right column in table).

To do the same with the CLI script you run this:

::

   [pathToYourTYPO3Installation]/typo3/cli_dispatch.phpsh crawler_im 3 -d 1 -conf tx_staticpub_publish

And this is the output:

::

   [22-03 15:29:00] ?id=3
   [22-03 15:29:00] ?id=3&L=1
   [22-03 15:29:00] ?id=5
   [22-03 15:29:00] ?id=4

At this point you have three options for “action”:

- Commit the URLs to the queue and let the cron script take care of it
  over time. In this case there is an option for setting the amount of
  tasks per minute if you wish to change it from the default 30. This is
  useful if you would like to submit a job to the cron script based
  crawler everyday.

  - Add “-o queue”

- List full URLs for use with wget or similar. Corresponds to pressing
  the “Download URLs” button in the backend module.

  - Add “-o url”

.. image:: /Images/cli_addtoque.png

- Commit and execute the queue right away. This will still put the jobs
  into the queue but execute them immediately. If server load is no
  issue to you and if you are in a hurry this is the way to go! It also
  feels much more like the “command-line-way” of things. And the status
  output is more immediate than in the queue.

  - Add “-o exec”

.. image:: /Images/cli_processque.png

The examples above assume that “staticpub” is installed.

