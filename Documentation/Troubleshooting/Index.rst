

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

Crawler want process all entries from command line
''''''''''''''''''''''''''''''''''''''''''''''''''

The crawler won't process all entrys at command-line-way. This might
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
