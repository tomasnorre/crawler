..  include:: /Includes.rst.txt

..  _example-configuration-news:

========
EXT:news
========

The news extensions is one of the most used extensions in the TYPO3 CMS. This
configuration is made under the assumption with a page tree looking similar to this:

..  figure:: /Images/ext_news_pagetree.png
    :alt: Example Pagetree of EXT:news setup

    Example Pagetree of EXT:news setup

If you want to have a Crawler Configuration that matches this, you can add
following to the :guilabel:`PageTS` for PageId `56`.

..  literalinclude:: _page.tsconfig
    :caption: packages/my_extension/Configuration/Sets/MySet/page.tsconfig

Now you can add the News detail-view pages to the crawler queue and have them in
the cache and the `indexed_search` index if you are using that.

..  _example-configuration-news-category:

Respecting Categories in News
=============================

On some installations news is configured in such a way, that news of category A
have their detail view on one page and news of category B have their detail view on
another page. In this case it would still be possible to view news of category A on
the detail page for category B (example.com/detail-page-for-category-B/news-of-category-A).
That means that each news article would be crawled twice - once on the detail page
for category A and once on the detail page for category B. It is possible to use a
PSR-14 event with news to prevent this.

On both detail pages include this typoscript setup:

..  literalinclude:: _setup.typoscript
    :caption: packages/my_extension/Configuration/Sets/MySet/setup.typoscript

and register an event listener in your site package.

..  literalinclude:: _services.yaml
    :caption: packages/my_extension/Configuration/Services.yaml

..  literalinclude:: _NewsDetailEventListener.php
    :caption: packages/my_extension/Classes/EventListeners/NewsDetailEventListener.php

..  warning::

    Note that this does more than just prevent articles from being indexed twice. It
    actually prevents articles from being displayed on a page that is supposed to show
    only articles of a certain category!
