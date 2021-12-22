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

Respecting Categories in News
=============================

On some installations news is configured in such a way, that news of category A
have their detail view on one page and news of category B have their detail view on
another page. In this case it would still be possible to view news of category A on
the detail page for category B (example.com/detail-page-for-category-B/news-of-category-A).
That means that each news article would be crawled twice - once on the detail page
for category A and once on the detail page for category B. It is possible to use a
signal slot within news to prevent this.

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

and register a signal slot in your site package.

:file:`ext/ext_localconf.php`

.. code-block:: php

    <?php

    defined('TYPO3_MODE') or die();

    (function() {
         if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news') === true) {
            $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
               TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
            );
            $signalSlotDispatcher->connect(
               GeorgRinger\News\Controller\NewsController::class,
               'detailAction',
               Vendor\Ext\Slots\NewsDetailSlot::class,
               'detailActionSlot',
               true
            );
        }
    })();

:file:`ext/Classes/Slots/NewsDetailSlot.php`

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace Vendor\Ext\Slots;

    class NewsDetailSlot
    {
      public function detailActionSlot($newsItem, $currentPage, $demand, $settings, $extendedVariables): array
      {
         if (!\is_null($newsItem)) {
               $demandedCategories = $demand->getCategories();
               $itemCategories = $newsItem->getCategories()->toArray();
               $itemCategoryIds = \array_map(function($category) {
                  return (string)$category->getUid();
               }, $itemCategories);
               /**
                * Unset the news article if necessary, which - in conjunction with the above TypoScript - will cause a 404 error
                */
               if (count($demandedCategories) > 0 && !$this->itemMatchesCategoryDemand($settings['categoryConjunction'], $itemCategoryIds, $demandedCategories)) {
                  $newsItem = null;
               }
         }
         return [
               'newsItem' => $newsItem,
               'currentPage' => $currentPage,
               'demand' => $demand,
               'settings' => $settings,
               'extendedVariables' => $extendedVariables,
         ];
      }

      protected function itemMatchesCategoryDemand(string $categoryConjunction, array $itemCategoryIds, array $demandedCategories): bool
      {
         $numOfDemandedCategories = \count($demandedCategories);
         $intersection = \array_intersect($itemCategoryIds, $demandedCategories);
         $numOfCommonItems = \count($intersection);
         switch ($categoryConjunction) {
               case 'AND':
                  return $numOfCommonItems === $numOfDemandedCategories;
                  break;
               case 'OR':
                  return $numOfCommonItems > 0;
                  break;
               case 'NOTAND':
                  return $numOfCommonItems < $numOfDemandedCategories;
                  break;
               case 'NOTOR':
                  return $numOfCommonItems === 0;
                  break;
               default:
                  return true;
                  break;
         }
      }
    }

.. warning::

   Note that this does more than just prevent articles from being indexed twice. It
   actually prevents articles from being displayed on a page that is supposed to show
   only articles of a certain category!
