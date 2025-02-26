..  include:: /Includes.rst.txt
..  highlight:: php

=====
Hooks
=====

Register the following hooks in :file:`ext_localconf.php` of your extension.

excludeDoktype Hook
===================

By adding doktype ids to following array you can exclude them from
being crawled:

::

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'][] = <dokTypeId>


pageVeto Hook
=============

..  deprecated:: 11.0.0
    Will be removed in 13.0, please migrate to the PSR-14 Event :ref:`psr14-modify-skip-page-event`!

You can also decide whether a page should not be crawled in an
individual userfunction. Register your function here:

::

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'][] = Vendor\YourExt\Hooks\Crawler\PageVeto::class . '->excludePage';

Example::

   <?php
    declare(strict_types=1);

    namespace Vendor\YourExt\Hooks\Crawler;

    use AOE\Crawler\Controller\CrawlerController;

    class PageVeto
   {
      public function excludePage(array &$params, CrawlerController $controller)
      {
         if ($params['pageRow']['uid'] === 42) {
            return 'Page with uid "42" is excluded by page veto hook');
         }

         return false;
      }
   }

