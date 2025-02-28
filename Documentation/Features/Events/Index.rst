..  include:: /Includes.rst.txt
..  _psr14-events:

=============
PSR-14 Events
=============

..  versionadded:: 11.0.0

You can register your own PSR-14 Event Listener and extend the functionality of the
TYPO3 Crawler. In this section you will see which events that you can listen too.

..  contents::
    :caption: Events within the Crawler
    :depth: 1
    :local:

..  _psr14-modify-skip-page-event:

ModifySkipPageEvent
===================

With this event, you can implement your own logic whether a page should be skipped
or not, this can be basically a skip by uid, like in the example below. It can
also be a more complex logic that determines if it should be skipped or not.

Let's say you don't want to crawl pages with SEO priority 0.2 or lower.
This would then be the place to add your own listener to Modify the Skip Page logic
already implemented.

..  rst-class:: bignums-xxl

#.  Create the event listener

    ..  literalinclude:: _ModifySkipPageEventListener.php
        :caption: packages/my_extension/Classes/EventListener/ModifySkipPageEventListener.php

#.  Register your event listener in :file:`Configuration/Services.yaml`

    ..  literalinclude:: _ModifySkipPageEventListener_services.yaml
        :caption: packages/my_extension/Configuration/services.yaml


..  _psr14-after-url-crawled-event:

AfterUrlCrawledEvent
====================

This event enables you to trigger, e.g a Vanish Ban for a specific URL after it's freshly
crawled. This ensures that your varnish cache will be up to date as well.

..  rst-class:: bignums-xxl

#.  Create the event listener

    ..  literalinclude:: _AfterUrlCrawledEventListener.php
        :caption: packages/my_extension/Classes/EventListener/AfterUrlCrawledEventListener.php

#.  Register your event listener in :file:`Configuration/Services.yaml`

    ..  literalinclude:: _AfterUrlCrawledEventListener_services.yaml
        :caption: packages/my_extension/Configuration/services.yaml

..  _psr14-invoke-queue-change-event:

InvokeQueueChangeEvent
======================

The InvokeQueueChangeEvent enables you to act on queue changes, it can be
e.g. automatically adding new processes. The event takes a `Reason` as arguments
which gives you more information about what has happened and for GUI also by
whom.

..  rst-class:: bignums-xxl

#.  Create the event listener

    ..  literalinclude:: _InvokeQueueChangeEventListener.php
        :caption: packages/my_extension/Classes/EventListener/AfterUrlCrawledEventListener.php

#.  Register your event listener in :file:`Configuration/Services.yaml`

    ..  literalinclude:: _InvokeQueueChangeEvent_services.yaml
        :caption: packages/my_extension/Configuration/services.yaml

..  _psr14-after-url-added-to-queue-event:

AfterUrlAddedToQueueEvent
=========================

AfterUrlAddedToQueueEvent gives you the opportunity to trigger desired actions based on
e.g. which fields are changed. You have `uid` and `fieldArray` present for evaluation.

..  rst-class:: bignums-xxl

#.  Create the event listener

    ..  literalinclude:: _AfterUrlAddedToQueueEventListener.php
        :caption: packages/my_extension/Classes/EventListener/AfterUrlAddedToQueueEventListener.php


#.  Register your event listener in :file:`Configuration/Services.yaml`

    ..  literalinclude:: _AfterUrlAddedToQueueEventListener_services.yaml
        :caption: packages/my_extension/Configuration/services.yaml

..  _psr14-before-queue-items-added-event:

BeforeQueueItemAddedEvent
=========================

This event can be used to check or modify a queue record before adding it to
the queue. This can be useful if you want certain actions in place based on, let's
say `Doktype` or SEO Priority.

..  rst-class:: bignums-xxl

#.  Create the event listener

    ..  literalinclude:: _BeforeQueueItemAddedEventListener.php
        :caption: packages/my_extension/Classes/EventListener/BeforeQueueItemAddedEventListener.php


#.  Register your event listener in :file:`Configuration/Services.yaml`

    ..  literalinclude:: _BeforeQueueItemAddedEventListener_services.yaml
        :caption: packages/my_extension/Configuration/services.yaml

..  _psr14-after-queue-items-added-event:

AfterQueueItemAddedEvent
========================

The AfterQueueItemAddedEvent can be helpful if you want a given action after
the item is added. Here you have the `queueId` and `fieldArray` information for you
usages and checks.

..  rst-class:: bignums-xxl

#.  Create the event listener

    ..  literalinclude:: _AfterQueueItemAddedEventListener.php
        :caption: packages/my_extension/Classes/EventListener/AfterQueueItemAddedEventListener.php

#.  Register your event listener in :file:`Configuration/Services.yaml`

    ..  literalinclude:: _AfterQueueItemAddedEventListener_services.yaml
        :caption: packages/my_extension/Configuration/services.yaml
