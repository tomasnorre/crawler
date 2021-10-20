.. include:: /Includes.txt

===============
Troubleshooting
===============

.. contents:: Table of Contents
   :depth: 1
   :local:

Problem reading data in Crawler Queue
=====================================

With the crawler release 9.1.0 we have changed the data stores in crawler queue
from serialized to json data. If you are experiencing problems with the old data
still in your database, you can flush your complete crawler queue and the
problem should be solved.

We have build in a `JsonCompatibilityConverter` to ensure that this should not
happen, but in case of it run:

::

    $ vendor/bin/typo3 crawler:flushQueue all


Make Direct Request doesn't work
================================

If you are using direct request, see :ref:`extension-manager-configuration`,
and it doesn't give you any result, or that the scheduler tasks stalls.

It can be because of a faulty configured `TrustedHostPattern`, this can be
changed in the :file:`LocalConfiguration.php`.

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '<your-pattern>';


Crawler want process all entries from command line
==================================================

The crawler won't process all entries at command-line-way. This might
happened because the php run into an time out, to avoid this you can
call the crawler like:

::

   php -d max_execution_time=512 vendor/bin/typo3 crawler:buildQueue


Crawler Count is 0 (zero)
=========================

If you experiences that the crawler queue only adds one url to the queue, you
are probably on a new setup, or an update from TYPO3 8LTS you might have some
migration not executed yet.

Please check the :guilabel:`Upgrade Wizard`, and check if the
:guilabel:`Introduce URL parts ("slugs") to all existing pages` is marked as
done, if not you should perform this step.

See related issue: `[BUG] Crawling Depth not respected #464 <https://github.com/tomasnorre/crawler/issues/464>`_


Update from older versions
==========================

If you update the extension from older versions you can run into following error:

.. code-block:: text

    SQL error: 'Field 'sys_domain_base_url' doesn't have a default value'

Make sure to delete all unnecessary fields from database tables. You can do
this in the backend via :guilabel:`Analyze Database Structure` tool or if you
have `TYPO3 Console <https://extensions.typo3.org/extension/typo3_console/>`_
installed via command line command
:shell:`vendor/bin/typo3cms database:updateschema`.


TYPO3 shows error if the PHP path is not correct
================================================

In some cases you get an error, if the PHP path is not set correctly. It occures
if you select the Site Crawler in Info-module.

.. figure:: /Images/backend_info_php_error.png
   :alt: Error message in Info-module

   Error message in Info-module

In this case you have to set the path to your PHP in the Extension configuration.

.. figure:: /Images/backend_php_path_configuration.png
   :alt: Correct PHP path settings

   Correct PHP path settings in Extension configuration

Please be sure to add the correct path to your PHP. The path in this screenshot
might be different to your PHP path.

Info Module throws htmlspecialchars() expects parameter 1 to be string
======================================================================

We have had a bug in the Crawler for a while, which I had difficulties
figuring out. The bug is cause by a problem with the CrawlerHook in the
TYPO3 Core, as this is remove in TYPO3 11.

I will not try to provide a fix for this, but only a workaround.

Workaround
----------
   The problem appears when the Crawler Configuration and the Indexed_Search Configuration are stored on the same page. The workaround is then to move the Indexed_Search Configuration to a different page. I have not experience any side-effects on this change, but if you do so, please report them to me.

This workaround is for these two bugs:

https://github.com/tomasnorre/crawler/issues/576 and
https://github.com/tomasnorre/crawler/issues/739

If you would like to know more about what's going it, you can look at the core:

https://github.com/TYPO3/TYPO3.CMS/blob/10.4/typo3/sysext/indexed_search/Classes/Hook/CrawlerHook.php#L156

Here a int value is submitted instead of a String. This is a change that goes more than 8 years back.
So surprised that it never was a problem before.
