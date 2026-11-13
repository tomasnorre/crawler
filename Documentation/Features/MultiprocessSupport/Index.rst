..  include:: /Includes.rst.txt

..  _multi-process:

====================
Parallel processing
====================

If you want to optimize the crawling process for speed (instead of low
server stress), maybe because the machine is a dedicated staging
machine, you can run several workers at the same time:

..  code-block:: bash

    vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low &
    vendor/bin/typo3 messenger:consume crawler_high crawler crawler_low &

Each queue entry is only processed by one of the workers. Choose the number of
workers carefully as every worker puts load on the server.
