﻿..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _introduction-what:

What does it do?
================

The TYPO3 Crawler is an extension which provides possibilities, from both
the TYPO3 backend and from CLIm that helps you with you cache and e.g.
search index.

The Crawler implements several PSR-14 events, that you can use to "hook" into
if you have certain requirements for your site at the given time.

See more :ref:`psr14-modify-skip-page-event`.

It features an API that other extensions can plug into. Example of this
is "indexed\_search" which uses crawler to index content defined by
its Indexing Configurations. Other extensions supporting it are
"staticpub" (publishing to static pages) or "cachemgm" (allows
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

..  _introduction-screenshots:

Screenshots
===========

The extension provides a backend module which displays the queue and log and
allows execution and status check of the "cronscript" from the backend for
testing purposes.

..  figure:: /Images/backend_processlist.png

    CLI status display

CLI = Command Line Interface = shell script = cron script

..  figure:: /Images/backend_crawlerlog.png

    Crawler queue (before processing) / log (after processing)

..  figure:: /Images/backend_pendingurls.png

    Interface for submitting a batch of URLs to be crawled

The parameter combinations are programmable through Page TSconfig or
configuration records.
