.. include:: /Includes.txt

========
EXT:news
========

The news extensions is one of the most used extensions in the TYPO3 CMS. This
configuration is made under the assumption with a page tree looking similar to this:

.. figure:: /Images/ext_news_pagetree.png
   :alt: Example Pagetree of EXT:news setup

   Example Pagetree of EXT:news setup

If you want to have a Crawler Configuration that matches this, you can add
following to the :guilabel:`PageTS` for PageId `56`.

Example
=======

::

   tx_crawler.crawlerCfg.paramSets {
      tx_news = &tx_news_pi1[controller]=News&tx_news_pi1[action]=detail&tx_news_pi1[news]=[_TABLE:tx_news_domain_model_news; _PID:57; _WHERE: hidden = 0]
      tx_news {
        pidsOnly = 58
      }
   }

   # _PID:57 is the Folder where news records are stored.
   # pidSOnly = 58 is the detail-view PageId.

Now you can add the News detail-view pages to the crawler queue and have them in
the cache and the `indexed_search` index if you are using that.
