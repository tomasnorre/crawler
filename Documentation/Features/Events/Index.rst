.. include:: /Includes.txt
.. highlight:: php

======
Events
======

You can register you own PSR-14 Event Listener and extend the functionality of the
TYPO3 Crawler. In this section you will see which events that you can listen too.

.. _psr14-modify-skip-page-event:

ModifySkipPageEvent
===================

With this event, you can implement you own logic weather a page should be skipped
or not, this can be basically a skip by uid, like in the example below. It can
also be a more complex logic that determines if it should be skipped or not.

Let's say you don't want to crawl pages with SEO priority 0.2 or lower.
This would then be the place to add your own listener to Modify the Skip Page logic
already implemented.

.. rst-class:: bignums-xxl

#. Create the event listener

   ::

      <?php
      declare(strict_types=1);

      namespace AOE\Crawler\EventListener;

      final class ModifySkipPageEventListener
      {
          public function __invoke(ModifySkipPageEvent $modifySkipPageEvent)
          {
              if($modifySkipPageEvent->getPageRow()['uid'] === 42) {
                  $modifySkipPageEvent->setSkipped('Page with uid "42" is excluded by ModifySkipPageEvent');
              }
              return false;
          }
      }

#. Register your event listener in :file:`Configuration/Services.yaml`

   .. code-block:: yaml

      services:
         AOE\Crawler\EventListener\ModifySkipPageEventListener:
            tags:
               -   name: event.listener
                   identifier: 'ext-extension-key/ModifySkipPageEventListener'
                   event: AOE\Crawler\Event\ModifySkipPageEvent


.. _psr14-after-url-crawled-event:

AfterUrlCrawledEvent
====================

This events enables you to trigger, e.g a Vanish Ban for a specific URL after it's freshly
crawled. This ensures that your varnish cache will be up to date as well.

.. rst-class:: bignums-xxl

#. Create the event listener

   ::

      <?php
      declare(strict_types=1);

      namespace AOE\Crawler\EventListener;

      final class AfterUrlCrawledEventListener
      {
          public function __invoke(AfterUrlCrawledEvent $afterUrlCrawledEvent)
          {
              $url = $afterUrlCrawledEvent->$afterUrl()
               // VarnishBanUrl($url);
          }
      }

#. Register your event listener in :file:`Configuration/Services.yaml`

   .. code-block:: yaml

      services:
         AOE\Crawler\EventListener\AfterUrlCrawledEvent:
            tags:
               -   name: event.listener
                   identifier: 'ext-extension-key/AfterUrlCrawledEventListener'
                   event: AOE\Crawler\Event\AfterUrlCrawledEvent
