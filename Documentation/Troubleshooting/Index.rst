

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


Troubleshooting
---------------

Problem reading data in Crawler Queue
'''''''''''''''''''''''''''''''''''''

With the crawler release 9.1.0 we have changed the data stores in crawler queue from serialized to json data.
If you are experiencing problems with the old data still in your database, you can flush your complete crawler queue
and the problem should be solved.

We have build in a `JsonCompatibiityConverter` to ensure that this should not happen, but in case of it run:

::

    $ vendor/bin/typo3 crawler:flushQueue all


Make Direct Request doesn't work
''''''''''''''''''''''''''''''''
If you are using direct request, see :ref:`extension-manager-configuration`, and it doesn't give you any result,
or that the scheduler tasks stalls.

It can be because of a faulty configured `TrustedHostPattern`, this can be changed in the `LocalConfiguration.php`.

::

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '<your-pattern>';

Crawler want process all entries from command line
''''''''''''''''''''''''''''''''''''''''''''''''''

The crawler won't process all entries at command-line-way. This might
happened because the php run into an time out, to avoid this you can
call the crawler like:

::

   php -d max_execution_time=512 [pathToYourTYPO3Installation-composer-bin-dir]/typo3cms crawler:buildqueue

Crawler Count is 0 (zero)
'''''''''''''''''''''''''

If you experiences that the crawler queue only adds one url to the queue, you are probably on a new setup,
or an update from TYPO3 8LTS you might have some migration not executed yet.

Please check the Upgrade Wizard, and check if the "Introduce URL parts ("slugs") to all existing pages"
is marked as done, if not you should perform this step.

See related issue: `[BUG] Crawling Depth not respected #464 <https://github.com/AOEpeople/crawler/issues/464>`_


Update from older versions
''''''''''''''''''''''''''

If you update the extension from older versions you can run into following error:

::

    SQL error: 'Field 'sys_domain_base_url' doesn't have a default value'

Make sure to delete all unnecessary fields from database tables. You can do this in the backend via "Analyze Database Structure"-Tool or if you have typo3-console installed via command line command "typo3cms database:updateschema".

