..  include:: /Includes.rst.txt
..  highlight:: typoscript

================================================
Page TSconfig Reference (tx\_crawler.crawlerCfg)
================================================

..  confval-menu::
    :name: page-tsconfig
    :display: table
    :type:

..  _crawler-tsconfig-paramSets-key:

..  confval:: paramSets.[key]
    :type: string

    Get Parameter configuration. The values of GET variables are according
    to a special syntax. From the code documentation
    (class.tx\_crawler\_lib.php):

    -   Basically: If the value is wrapped in [...] it will be expanded
        according to the following syntax, otherwise the value is taken
        literally

    -   Configuration is splitted by "\|" and the parts are processed
        individually and finally added together

    -   For each configuration part:

    -   "[int]-[int]" = Integer range, will be expanded to all values in
         between, values included, starting from low to high (max. 1000).
         Example "1-34" or "-40--30"

    -   "**\_TABLE:**" in the beginning of string indicates a look up in a
         table. Syntax is a string where [keyword]:[value] pairs are separated
         by semi-colon. Example "\_TABLE:tt\_content; \_PID:123"

        -   Keyword " **\_TABLE** ": (mandatory, starting string): Value is table
            name from TCA to look up into.

        -   Keyword " **\_ADDTABLE** ": Additional tables to fetch data from.
            This value will be appended to "\_TABLE" and used as "FROM"
            part of SQL query.

        -   Keyword " **\_PID** ": Value is optional page id to look in (default
            is current page).

        -   Keyword " **\_RECURSIVE** ": Optional flag to set recursive crawl
            depth. Default is 0.

        -   Keyword " **\_FIELD** ": Value is field name to use for the value
            (default is uid).

        -   Keyword " **\_PIDFIELD** ": Optional value that contains the name of
            the column containing the pid. By default this is "pid".

        -   Keyword " **\_ENABLELANG** ": Optional flag. If set only the records
            from the current language are fetched.

        -   Keyword " **\_WHERE** ": Optional flag. This can be use to e.g. if
            you don't want hidden records to be crawled.

    -   \- Default: Literal value

    **Examples:**

    ..  code-block:: plaintext

        &L=[|1|2|3]

        &L=[0-3]

    ..  literalinclude:: _paramSets_page.tsconfig
        :caption: packages/my_extension/Configuration/Sets/MySet/page.tsconfig


..  _crawler-tsconfig-paramSets-key-procInstrFilter:

..  confval:: paramSets.[key].procInstrFilter
    :type: string

     List of processing instructions, eg. "tx\_indexedsearch\_reindex" from
     indexed\_search to send for the request. Processing instructions are
     necessary for the request to perform any meaningful action, since they
     activate third party activity.

..  _crawler-tsconfig-paramSets-key-procInstrParams:

..  confval:: paramSets.[key].procInstrParams.[procIn.key].[...]
    :type: strings

     Options for processing instructions. Will be defined in the respective
     third party modules.

     **Examples:**

     `procInstrParams.tx_staticpub_publish.includeResources=1`

..  _crawler-tsconfig-paramSets-key-pidsOnly:

..  confval:: paramSets.[key].pidsOnly
    :type: list of integers (pages uid)

         List of Page Ids to limit this configuration to

..  _crawler-tsconfig-paramSets-key-force_ssl:

..  confval:: paramSets.[key].force_ssl
    :type: integer

     Whether https should be enforced or not. 0 = false (default), 1 = true.

..  _crawler-tsconfig-paramSets-key-userGroups:

..  confval:: paramSets.[key].userGroups
    :type: list of integers (fe\_groups uid)

     User groups to set for the request.

..  _crawler-tsconfig-paramSets-key-baseUrl:

..  confval:: paramSets.[key].baseUrl
    :type: string

     If not set, t3lib\_div::getIndpEnv('TYPO3\_SITE\_URL') is used to
     request the page.

     MUST BE SET if run from CLI (since TYPO3\_SITE\_URL does not exist in
     that context!)


[Page TSconfig: tx\_crawler.crawlerCfg]


Example
=======

..  literalinclude:: _page.tsconfig
    :caption: packages/my_extension/Configuration/Sets/MySet/page.tsconfig
