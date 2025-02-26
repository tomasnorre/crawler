..  include:: /Includes.rst.txt

..  _backend-configuration-record:

=====================
Configuration records
=====================

Formerly configuration was done by using pageTS (see below). This is
still possible (fully backwards compatible) but not recommended.
Instead of writing pageTS simply create a configuration record (table:
``tx_crawler_configuration``) and put it on the topmost page of the
pagetree you want to affect with this configuration.

The fields in these records are related to the pageTS keys described
below.

Fields and their pageTS equivalents
===================================

General
-------

..  figure:: /Images/backend_configurationrecord_general.png
    :alt: Backend configuration record: General

    Backend configuration record: General

Name
    Corresponds to the "key" part in the pageTS setup e.g.
    :typoscript:`tx_crawler.crawlerCfg.paramSets.myConfigurationKeyName`

Protocol for crawling
    Force HTTP, HTTPS or keep the configured protocol

Processing instruction filter
    List of processing instructions. See also:
    :ref:`paramSets.[key].procInstrFilter <crawler-tsconfig-paramSets-key-procInstrFilter>`

Base URL
    Set baseUrl (most likely the same as the entry point configured in your
    site configuration)

Pids only
    List of Page Ids to limit this configuration to. See also:
    :ref:`paramSets.[key].pidsOnly <crawler-tsconfig-paramSets-key-pidsOnly>`

Exclude pages
    Comma separated list of page ids which should not be crawled.
    You can do recursive exclusion by adding `uid`+`depth` e.g. 6+3,
    this will ensure that all pages including pageUid 6 and 3 levels down
    will not be crawled.

Configuration
    Parameter configuration. The values of GET variables are according to a
    special syntax. See also: :ref:`paramSets.[key]
   <crawler-tsconfig-paramSets-key>`

Processing instruction parameters
    Options for processing instructions. Will be defined in the respective third
    party modules. See also: :ref:`paramSets.[key].procInstrParams
   <crawler-tsconfig-paramSets-key-procInstrParams>`

Crawl with FE user groups
    User groups to set for the request. See also:
    :ref:`paramSets.[key].userGroups <crawler-tsconfig-paramSets-key-userGroups>` and the hint in :ref:`create-crawler-configuration`

Access
------

..  figure:: /Images/backend_configurationrecord_access.png
    :alt: Backend configuration record: Access

    Backend configuration record: Access

Hide
    If activated the configuration record is not taken into account.

Restrict access to
    Restricts access to this configuration record to selected backend user
    groups. Empty means no restriction is set.
