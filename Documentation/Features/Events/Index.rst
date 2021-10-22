.. include:: /Includes.txt
.. highlight:: php

=============
PSR-14 Events
=============

Since 11.0.0

You can register your own PSR-14 Event Listener and extend the functionality of the
TYPO3 Crawler. In this section you will see which events that you can listen too.

**Events within the Crawler**

* :ref:`psr14-modify-skip-page-event`
* :ref:`psr14-after-url-crawled-event`
* :ref:`psr14-invoke-queue-change-event`
* :ref:`psr14-after-urla-added-to-queue-event`
* :ref:`psr14-before-queue-items-added-event`
* :ref:`psr14-after-queue-items-added-event`

.. _psr14-modify-skip-page-event:

ModifySkipPageEvent
===================

With this event, you can implement your own logic weather a page should be skipped
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
               // VarnishBanUrl($afterUrlCrawledEvent->$afterUrl());
          }
      }

#. Register your event listener in :file:`Configuration/Services.yaml`

   .. code-block:: yaml

      services:
         AOE\Crawler\EventListener\AfterUrlCrawledEventListener:
            tags:
               -   name: event.listener
                   identifier: 'ext-extension-key/AfterUrlCrawledEventListener'
                   event: AOE\Crawler\Event\AfterUrlCrawledEvent

.. _psr14-invoke-queue-change-event:

InvokeQueueChangeEvent
======================

The InvokeQueueChangeEvent enables you to act on queue changes, it can be
e.g. automatically adding new processes. The event takes a `Reason` as arguments
which gives you more information about what has happened and for GUI also by
whom.

.. rst-class:: bignums-xxl

#. Create the event listener

   ::

      <?php
      declare(strict_types=1);
      namespace AOE\Crawler\EventListener;

      final class InvokeQueueChangeEventListener
      {
          public function __invoke(InvokeQueueChangeEvent $invokeQueueChangeEvent)
          {
               $reason = $invokeQueueChangeEvent->getReason()
               // You can implement different logic based on reason, GUI or CLI
          }
      }

#. Register your event listener in :file:`Configuration/Services.yaml`

   .. code-block:: yaml

      services:
         AOE\Crawler\EventListener\InvokeQueueChangeEvent:
            tags:
               -   name: event.listener
                   identifier: 'ext-extension-key/InvokeQueueChangeEventListener'
                   event: AOE\Crawler\Event\InvokeQueueChangeEvent

.. _psr14-after-urla-added-to-queue-event:

AfterUrlAddedToQueueEvent
=========================

AfterUrlAddedToQueueEvent gives you the opportunity to trigger desired actions based on
e.g. which fields are changed. You have `uid` and `fieldArray` present for evaluation.

.. rst-class:: bignums-xxl

#. Create the event listener

   ::

      <?php
      declare(strict_types=1);
      namespace AOE\Crawler\EventListener;

      final class AfterUrlAddedToQueueEventListener
      {
          public function __invoke(AfterUrlAddedToQueueEvent $afterUrlAddedToQueueEvent)
          {
               // Implement your wanted logic, you have the `$uid` and `$fieldArray` information
          }
      }

#. Register your event listener in :file:`Configuration/Services.yaml`

   .. code-block:: yaml

      services:
         AOE\Crawler\EventListener\AfterUrlAddedToQueueEventListener:
            tags:
               -   name: event.listener
                   identifier: 'ext-extension-key/AfterUrlAddedToQueueEventListener'
                   event: AOE\Crawler\Event\AfterUrlAddedToQueueEvent

.. _psr14-before-queue-items-added-event:

BeforeQueueItemAddedEvent
=========================

This event can be used to check or modify a queue record before adding it to
the queue. This can be useful if you want certain actions in place based on lets
say `Doktype` or SEO Priority.

.. rst-class:: bignums-xxl

#. Create the event listener

   ::

      <?php
      declare(strict_types=1);
      namespace AOE\Crawler\EventListener;

      final class BeforeQueueItemAddedEventListener
      {
          public function __invoke(BeforeQueueItemAddedEvent $beforeQueueItemAddedEvent)
          {
               // Implement your wanted logic, you have the `$queueId` and `$queueRecord` information
          }
      }

#. Register your event listener in :file:`Configuration/Services.yaml`

   .. code-block:: yaml

      services:
         AOE\Crawler\EventListener\BeforeQueueItemAddedEventListener:
            tags:
               -   name: event.listener
                   identifier: 'ext-extension-key/BeforeQueueItemAddedEventListener'
                   event: AOE\Crawler\Event\BeforeQueueItemAddedEvent

.. _psr14-after-queue-items-added-event:

AfterQueueItemAddedEvent
========================

The AfterQueueItemAddedEvent can be helpful if you want a given action after
the item is added. Here you have the `queueId` and `fieldArray` information for you
usages and checks.

.. rst-class:: bignums-xxl

#. Create the event listener

   ::

      <?php
      declare(strict_types=1);
      namespace AOE\Crawler\EventListener;

      final class AfterQueueItemAddedEventListener
      {
          public function __invoke(AfterQueueItemAddedEvent $afterQueueItemAddedEvent)
          {
               // Implement your wanted logic, you have the `$queueId` and `$fieldArray` information
          }
      }

#. Register your event listener in :file:`Configuration/Services.yaml`

   .. code-block:: yaml

      services:
         AOE\Crawler\EventListener\AfterQueueItemAddedEventListener:
            tags:
               -   name: event.listener
                   identifier: 'ext-extension-key/AfterQueueItemAddedEventListener'
                   event: AOE\Crawler\Event\AfterQueueItemAddedEvent
