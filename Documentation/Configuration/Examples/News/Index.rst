..  include:: /Includes.rst.txt

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

Example
=======

::

    tx_crawler.crawlerCfg.paramSets {
      tx_news = &tx_news_pi1[controller]=News&tx_news_pi1[action]=detail&tx_news_pi1[news]=[_TABLE:tx_news_domain_model_news; _PID:58; _WHERE: hidden = 0]
      tx_news {
        pidsOnly = 57
      }
   }

   # _PID:58 is the Folder where news records are stored.
   # pidSOnly = 57 is the detail-view PageId.

Now you can add the News detail-view pages to the crawler queue and have them in
the cache and the `indexed_search` index if you are using that.

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
::

    plugin.tx_news.settings {
        # categories and categoryconjunction are not considered in detail view, so they must be overridden
        overrideFlexformSettingsIfEmpty = cropMaxCharacters,dateField,timeRestriction,archiveRestriction,orderBy,orderDirection,backPid,listPid,startingpoint,recursive,list.paginate.itemsPerPage,list.paginate.templatePath,categories,categoryConjunction
        # see the news extension for possible values of categoryConjunction
        categoryConjunction = AND
        categories = <ID of respective category>
        detail.errorHandling = pageNotFoundHandler
    }

and register an event listener in your site package.

:file:`ext/Configuration/Services.yaml`

..  code-block:: yaml

    services:
     Vendor\Ext\EventListeners\NewsDetailEventListener:
       tags:
         - name: event.listener
           identifier: 'myNewsDetailListener'
           event: GeorgRinger\News\Event\NewsDetailActionEvent


:file:`ext/Classes/EventListeners/NewsDetailEventListener.php`

..  code-block:: php

    <?php

    declare(strict_types=1);

    namespace Vendor\Ext\EventListeners;

    use GeorgRinger\News\Event\NewsDetailActionEvent;

    class NewsDetailEventListener
    {
       public function __invoke(NewsDetailActionEvent $event): void
       {
          $assignedValues = $event->getAssignedValues();
          $newsItem = $assignedValues['newsItem'];
          $demand = $assignedValues['demand'];
          $settings = $assignedValues['settings'];

          if (!\is_null($newsItem)) {
             $demandedCategories = $demand->getCategories();
             $itemCategories = $newsItem->getCategories()->toArray();
             $itemCategoryIds = \array_map(function($category) {
                return (string)$category->getUid();
             }, $itemCategories);

             if (count($demandedCategories) > 0 && !$this::itemMatchesCategoryDemand($settings['categoryConjunction'], $itemCategoryIds, $demandedCategories)) {
                $assignedValues['newsItem'] = null;
                $event->setAssignedValues($assignedValues);
             }
          }
       }

       protected static function itemMatchesCategoryDemand(string $categoryConjunction, array $itemCategoryIds, array $demandedCategories): bool
       {
          $numOfDemandedCategories = \count($demandedCategories);
          $intersection = \array_intersect($itemCategoryIds, $demandedCategories);
          $numOfCommonItems = \count($intersection);

          switch ($categoryConjunction) {
                case 'AND':
                   return $numOfCommonItems === $numOfDemandedCategories;
                case 'OR':
                   return $numOfCommonItems > 0;
                case 'NOTAND':
                   return $numOfCommonItems < $numOfDemandedCategories;
                case 'NOTOR':
                   return $numOfCommonItems === 0;
          }
          return true;
       }
    }

..  warning::

    Note that this does more than just prevent articles from being indexed twice. It
    actually prevents articles from being displayed on a page that is supposed to show
    only articles of a certain category!
