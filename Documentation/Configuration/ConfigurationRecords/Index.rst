

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


Configuration records
^^^^^^^^^^^^^^^^^^^^^

Formerly configuration was done by using pageTS (see below). This is
still possible (fully backwards compatible) but not recommended.
Instead of writing pageTS simply create a configuration record (table:
tx\_crawler\_configuration) and put it on the topmost page of the
pagetree you want to affect with this configuration.

The fields in these records are related to the page ts keys described
below.

Fields and their pageTS equivalents
'''''''''''''''''''''''''''''''''''

- **Name** - corresponds to the "key" part in the pageTS setup
  e.g. tx_crawler.crawlerCfg.paramSets.myConfigurationKeyName

- **Processing instruction filter** - :ref:`paramSets.[key].procInstrFilter <crawler-tsconfig-paramSets-key-procInstrFilter>`

- **Configuration** - :ref:`paramSets.[key] <crawler-tsconfig-paramSets-key>`

- **Get Baseurl from Domainrecord** - :ref:`paramSets.[key].baseUrl <crawler-tsconfig-paramSets-key-baseUrl>`

- **Pids only** - :ref:`paramSets.[key].pidsOnly <crawler-tsconfig-paramSets-key-pidsOnly>`

- **Processing instruction parameters**

- **Restrict access to** - restricts access to this configuration record to selected backend user groups. Empty means no restriction is set.

- **Crawl with FE usergroups** - :ref:`paramSets.[key].userGroups <crawler-tsconfig-paramSets-key-userGroups>`

- **Append cHash** - :ref:`paramSets.[key].cHash <crawler-tsconfig-paramSets-key-cHash>`

- **Exclude pages** - comma separated list of page ids which should not be crawled

.. image:: /Images/backend_configurationrecord.png

