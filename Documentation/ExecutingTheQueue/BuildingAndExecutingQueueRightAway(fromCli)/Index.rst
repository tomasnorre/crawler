..  include:: /Includes.rst.txt

..  _executing-the-queue-cli-label:

==================================================
Building and Executing queue right away (from cli)
==================================================

An alternative mode is to automatically build and execute the queue
from the command line in one process. This doesn't allow scheduling of
task processing and consumes as much CPU as it can. On the other hand
the job is done right away. In this case the queue is both built and
executed right away.

The script to use is this:

..  code-block:: bash

    vendor/bin/typo3 crawler:buildQueue <startPageUid> <configurationKeys>

If you run it you will see a list of options which explains usage.


..  confval:: <startPageUid>
    :type: integer

    Page Id of the page to use as starting point for crawling.

..  confval:: <configurationKeys>
    :type: string

    Configurationkey:

    Comma separated list of your crawler configurations. If you use the
    crawler configuration records you have to use the "name" if you're
    still using the old TypoScript based configuration you have to use the
    configuration key which is also a string.

    **Examples:**

    ..  code-block:: plaintext

       re-crawl-pages,re-crawl-news


..  confval:: --number <number>
    :type: integer

    Specifies how many items are put in the queue per minute. Only valid
    for output mode "queue".


..  confval:: --mode <mode>
    :type: string
    :default: queue

    Output mode: "url", "exec", "queue"

    - url : Will list URLs which wget could use as input.

    - queue: Will put entries in queue table.

    - exec: Will execute all entries right away!


..  confval:: --depth <depth>
    :type: integer
    :default: 0

     Tree depth, 0-99.

     How many levels under the 'page\_id' to include. By default, no additional levels are included.


..  _executing-the-queue-cli-label-example:

Example
-------

We want to crawl pages under the page "Content Examples" (uid=6) and 2 levels down, with the default crawler configuration.

This is done like this in the backend.

..  image:: /Images/backend_startcrawling.png

To do the same with the CLI script you run this:

..  code-block:: bash

    vendor/bin/typo3 crawler:buildQueue 6 default --depth 2

And this is the output:

..  literalinclude:: _output_buildQueue_6_default.txt


At this point you have three options for "action":

-   Commit the URLs to the queue and let the cron script take care of it
    over time. In this case there is an option for setting the amount of
    tasks per minute if you wish to change it from the default 30. This is
    useful if you would like to submit a job to the cron script based
    crawler everyday.

    - Add "--mode queue"
    - This is also the **default** setting, so unless you want it to be explicit visible, you don't need to add it.

-   List full URLs for use with wget or similar. Corresponds to pressing
    the "Download URLs" button in the backend module.

    - Add "--mode url"

    ..  literalinclude:: _output_buildQueue_6_default_mode_url.txt

-   Commit and execute the queue right away. This will still put the jobs
    into the queue but execute them immediately. If server load is no
    issue to you and if you are in a hurry this is the way to go! It also
    feels much more like the "command-line-way" of things. And the status
    output is more immediate than in the queue.

    - Add "--mode exec"

    ..  literalinclude:: _output_buildQueue_6_default_mode_exec.txt

