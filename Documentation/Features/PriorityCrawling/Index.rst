..  include:: /Includes.rst.txt

..  _priority-crawling:

=================
Priority Crawling
=================

..  versionadded:: 9.1.0

Some website has a quite large number of pages. Some pages are logically more
important than others e.g. the start-, support-, product-, you name it-pages.
These important pages are also the pages where we want to have the best caching
and performance, as they will most likely be the pages with the most changes and
the most traffic.

With TYPO3 10 LTS the `sysext/seo` introduced among other things, the
`sitemap_priority`, which is used to generate an SEO optimised sitemap.xml
where page priorities are listed as well. Their priorities will most likely be higher the
more important the page is for you and the end-user.

This logic is something that we can benefit from in the Crawler as well. A
Website with let us say 10.000 pages, will have different importance depending on
the page you are at. Therefore the crawler takes the value of this field, range
from 0.0 to 1.0, into consideration when adding pages to the queue. Every
priority has its own transport on the message bus:

==================  ==========  ================
`sitemap_priority`  Priority    Transport
==================  ==========  ================
0.6 - 1.0           1 (high)    `crawler_high`
0.5 (default)       2 (normal)  `crawler`
0.0 - 0.4           3 (low)     `crawler_low`
==================  ==========  ================

The worker consumes the transports in the order they are given, so the pages
with a high priority are always crawled first:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low

URLs that are re-crawled manually from the :guilabel:`Crawler log` are always
sent to `crawler_high`.

..  versionchanged:: 14.0.0
    The priority is no longer used to sort the queue, but to select the
    transport of the message bus, see :ref:`upgrade-message-bus`.

This ensures that we will always crawl the pages that have the highest importance to
you and your end-user based on your sitemap priority. We choose to
reuse this field, to not have editors doing work that is more or less similar twice.

If you don't want to use this functionality, it's ok. You can just ignore the
options that the `sysext/seo` gives you and all pages will by default get a priority
0.5, and therefore end up in the `crawler` transport. The same applies when
`sysext/seo` is not installed.

The existing :guilabel:`SEO` tab will be used to set priorities when editing
pages.

..  image:: /Images/backend_crawler_seo_v10.png

..  figure:: /Images/backend_crawler_seo_priority_v10.png
    :alt: The SEO tab will contain the sitemap_priority field

    The SEO tab will contain the sitemap_priority field
