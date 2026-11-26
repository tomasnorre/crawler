..  include:: /Includes.rst.txt

..  _exclude-from-crawling:

=====================
Exclude from crawling
=====================

..  versionadded:: 14.0.0

Editors can exclude a page from crawling in the page properties, like
:guilabel:`Exclude from search`. No crawler configuration has to be changed.

Open the page properties, go to the :guilabel:`Behavior` tab and use the
:guilabel:`Crawler` section:

:guilabel:`Exclude from crawling`
    The crawler skips this page. Its subpages are still crawled.

:guilabel:`Exclude subpages from crawling`
    Only together with :guilabel:`Exclude from crawling`: the crawler also
    skips all subpages of this page, on every level. Enabled on a page that is
    not excluded itself, the option does nothing.

The options apply to every crawler configuration, from page TSconfig and from
configuration records. They are stored in the fields `tx_crawler_exclude` and
`tx_crawler_exclude_subpages` of the `pages` table.

Example
=======

..  code-block:: none

    Root
    ├── Page1             "Exclude from crawling"                                    → skipped
    │   ├── subpage1                                                                 → crawled
    │   ├── subpage2      "Exclude from crawling" + "Exclude subpages from crawling" → skipped
    │   │   ├── subsubpage1                                                          → skipped
    │   │   └── subsubpage2                                                          → skipped
    │   └── subpage3                                                                 → crawled
    ├── Page2                                                                        → crawled
    └── Page3                                                                        → crawled

How it works
============

Before the URLs of a page are added to the queue, the crawler checks whether
the page should be skipped, see
`AOE\Crawler\Service\PageService::checkIfPageShouldBeSkipped()`. Besides
hidden pages and excluded page types, it reads the rootline of the page and
skips the page if

*   the page itself has :guilabel:`Exclude from crawling` enabled, or
*   a parent page, on any level, has both :guilabel:`Exclude from crawling`
    and :guilabel:`Exclude subpages from crawling` enabled.

This applies to everything that adds pages to the queue: the
:guilabel:`Start Crawling` backend module, the `crawler:buildQueue` command and
:ref:`adding pages automatically <add-to-queue>` when they are edited or their
cache is cleared.

The :guilabel:`Start Crawling` module and the `queue` mode of
`crawler:buildQueue` show why a page is skipped:

*   `Because page is excluded from crawling`
*   `Because parent page "4" is excluded from crawling with its subpages`

Some details:

*   The rootline follows the real location of a page in the page tree. A page
    that is crawled through a mount point is excluded by its own parent pages,
    not by the parents of the mount point.
*   Translated pages use the options of the page in the default language.
*   Deleted parent pages are not taken into account.

Exclude pages per configuration
===============================

To exclude pages from a single crawler configuration only, use the
:guilabel:`Exclude pages` field of the
:ref:`configuration record <backend-configuration-record-general>`. For
exclusion rules of your own, listen to the
:ref:`ModifySkipPageEvent <psr14-modify-skip-page-event>`.
