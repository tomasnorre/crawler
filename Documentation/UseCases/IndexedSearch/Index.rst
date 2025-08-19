..  include:: /Includes.rst.txt

..  _use-case-indexed-search:

==============
Indexed Search
==============

The TYPO3 Crawler is quite often used to regenerate the index
of `Indexed Search <https://docs.typo3.org/permalink/typo3/cms-indexed-search:start>`_.

..  _use-case-indexed-search-setup:


Frontend indexing setup
=======================
Here we will configure `indexed_search` to automatically index pages when
they are visited by users in the frontend.


1. Make sure you do not have
   `config.no_cache <https://docs.typo3.org/permalink/t3tsref:confval-config-no-cache>`_
   set in your TypoScript configuration - this prevents indexing.

2. :guilabel:`Admin Tools > Settings > Extension configuration > indexed_search`:
   Make sure "Disable Indexing in Frontend" is disabled
   (thus frontend indexing is enabled).

3. :guilabel:`Web > List`: In your site root, create a new "Indexing Configuration" record.

   - Type: `Page tree`
   - Depth: `4`
   - Access > Enable: Activate it

   Save.

4. Edit the page settings of a visible page and make sure that
   :guilabel:`Behaviour > Miscellaneous > Include in search` is activated.

5. View this page in the frontend.

6. :guilabel:`Web > Indexing > Detailed statistics`: The page you just visited
   is shown as "Indexed" now - with Filename, Filesize and indexing
   timestamp.

If this did not work, clear both frontend and all caches.
Getting frontend indexing to work is crucial for the rest of this How-To.


Crawler setup
=============
Now that frontend indexing works, it's time to configure crawler
to re-index all pages - instead of relying on visitors to trigger
indexing:

1. :guilabel:`Admin Tools > Settings > Extension configuration > indexed_search`:
   Enable "Disable Indexing in Frontend", so that indexing only happens through
   the crawler.

2. Web > List: In your site root, create a new "Crawler configuration" record.

   - Name: `crawl-mysite`
   - Processing instruction filter: Enable "Re-Indexing [tx_indexedsearch_reindex]"

   Save.

3. Do a manual crawl on command line. "23" is the site root page UID::

     $ ./vendor/bin/typo3 crawler:buildQueue 23 crawl-mysite --depth 2 --mode exec -vvv

     Executing 2 requests right away:
     [19-08-25 14:13] http://example.org/ (URL already existed)
     [19-08-25 14:13] http://example.org/faq (URL already existed)
     <warning>Internal:  (Because page is hidden)</warning>
     <warning>Tools:  (Because doktype "254" is not allowed)</warning>
     Processing

     http://example.org/ (tx_indexedsearch_reindex) =>

     OK:
        User Groups:

     http://example.org/faq (tx_indexedsearch_reindex) =>

     OK:
        User Groups:

     2/2 [============================] 100%  1 sec/1 sec  42.0 MiB

4. :guilabel:`Web > Indexing`: All pages should be indexed now.


Nightly crawls
==============
We want `crawler` to run automatically at night:

1. Create the first scheduler task that will create a list with page URLs
   that the second task will crawl.

   :guilabel:`System > Scheduler > +`:

   - "Class" is "Execute console commands"
   - "Frequency" is every night at 2 o'clock: `0 2 * * *`
   - "Schedulable Command" must be "crawler:buildQueue"

   Save and continue editing:

   - "Argument: page" must be the UID of the site root page (`23`)
   - "Argument: conf" is `crawl-mysite`
   - "Option: depth" must be enabled and set to `99`

   Save.

2. Run the task manually, either via the scheduler module in the backend
   or via command line::

     $ ./vendor/bin/typo3 scheduler:run --task=1 -f -vv
     Task #1 was executed

   (`1` is the scheduler task ID)

3. Verify that the pages have been queued by looking at
   :guilabel:`Web > Info > Site Crawler > Crawler log > 2 levels`.
   The pages have a timestamp in the "Scheduled" column.

4. Create the second scheduler task that will index all the page URLs
   queued by the first task:

   :guilabel:`System > Scheduler > +`:

   - "Class" is "Execute console commands"
   - "Frequency" is every 10 minutes: `*/10 * * * *`
   - "Schedulable Command" must be "crawler:processQueue"

   Save and continue editing:

   - "Option: amount" should be `50`, or any value that the system is
     able to process within the 10 minutes.

   Save.

5. Run the task manually, again via the scheduler module in the backend
   (only if it's a small page!) or via command line::

     $ ./vendor/bin/typo3 scheduler:run --task=2 -f -vv
     Task #2 was executed

   This crawl task will run much longer that the queue task.

6. Verify that the pages have been indexed by looking at
   :guilabel:`Web > Indexing`.
   All queued pages should have an index date now.

   :guilabel:`Web > Info > Site Crawler > Crawler log > 2 levels`
   should show a timestamp in the "Run-time" column, as well as
   `OK` in the "Status" column.

This completes the basic crawler setup.
Every night at 2:00, all pages will be re-indexed in batches of 50.
