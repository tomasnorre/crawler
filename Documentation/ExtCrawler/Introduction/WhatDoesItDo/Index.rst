

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


What does it do?
^^^^^^^^^^^^^^^^

Uses a command line cron-script to traverse a queue of actions
(alternative option: executing all immediately), eg. requesting a URL
but could also be calling of a function. The name “crawler” refers to
this action: That a URL in the queue is requested.

Features an API that other extensions can plug into. Example of this
is “indexed\_search” which uses crawler to index content defined by
its Indexing Configurations. Other extensions supporting it are
“staticpub” (publishing to static pages) or “cachemgm” (allows
recaching of pages).

The requests of URLs is specially designed to request TYPO3 frontends
with special processing instructions. The requests sends a TYPO3
specific header in the GET requests which identifies a special action.
For instance the action requested could be to publish the URL to a
static file or it could be to index its content - or re-cache the
page. These processing instructions are also defined by third-party
extensions (and indexed search is one of them). In this way a
processing instruction can instruct the frontend to perform an action
(like indexing, publishing etc.) which cannot be done with a request
from outside.

