.. include:: /Includes.rst.txt

==============
Indexed Search
==============

The TYPO3 Crawler is quite often used for generated the Index of Indexed Search.

Unfortunately we don't have any good documentation included on this, but you can help in two ways.

1. You can help write the documentations
2. You can tip into the money pool, to help sponsor those writing the documentation.

You can see the issue here: https://github.com/tomasnorre/crawler/issues/558
or tip in the money pool here: https://www.paypal.com/paypalme/tomasnorre/10

`#HappyCrawling`

Setup Index Search
==================

With the latest improvements of the TYPO3 Crawler and Indexed Search, it's gotten
easier to set up Indexed Search to work with the TYPO3 Crawler.

You need a few things to have this working.

1. Create a :ref:`backend-configuration-record`
2. Setup add a Indexed Search Configuration (See: https://docs.typo3.org/c/typo3/cms-indexed-search/main/en-us/IndexingConfigurations/Configurations/Index.html)

If you want to index e.g. PDF files please ensure that you have the
respective tools installed on your server. For PDFs that would be `pdftotext` and
`pdfinfo`.
