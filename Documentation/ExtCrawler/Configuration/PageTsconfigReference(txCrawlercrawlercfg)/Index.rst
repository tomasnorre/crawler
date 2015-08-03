

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


Page TSconfig Reference (tx\_crawler.crawlerCfg)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:

   Data type
         Data type:

   Description
         Description:

   Default
         Default:


.. container:: table-row

   Property
         paramSets.[key]

   Data type
         string

   Description
         Get Parameter configuration. The values of GET variables are according
         to a special syntax. From the code documentation
         (class.tx\_crawler\_lib.php):

         - Basically: If the value is wrapped in [...] it will be expanded
           according to the following syntax, otherwise the value is taken
           literally

         - Configuration is splitted by "\|" and the parts are processed
           individually and finally added together

         - For each configuration part:

           - "[int]-[int]" = Integer range, will be expanded to all values in
             between, values included, starting from low to high (max. 1000).
             Example "1-34" or "-40--30"

           - " **\_TABLE** :” in the beginning of string indicates a look up in a
             table. Syntax is a string where [keyword]:[value] pairs are separated
             by semi-colon. Example "\_TABLE:tt\_content; \_PID:123"

             - Keyword “ **\_TABLE”** (mandatory, starting string): Value is table
               name from TCA to look up into.

             - Keyword “ **\_PID** ”: Value is optional page id to look in (default
               is current page).

             - Keyword “ **\_FIELD** ”: Value is field name to use for the value
               (default is uid).

             - Keyword “ **\_PIDFIELD** ”: Optional value that contains the name of
               the column containing the pid. By default this is “pid”.

             - Keyword “ **\_ENABLELANG** ”: Optional flag. If set only the records
               from the current language are fetched.

           - \- Default: Literal value

         **Examples:**

         ::

            &L=[|1|2|3]

            &L=[0-3]

   Default


.. container:: table-row

   Property
         paramSets.[key].procInstrFilter

   Data type
         string

   Description
         List of processing instructions, eg. “tx\_indexedsearch\_reindex” from
         indexed\_searchto send for the request. Processing instructions are
         necessary for the request to perform any meaningful action, since they
         activate third party activity.

   Default


.. container:: table-row

   Property
         paramSets.[key].procInstrParams.[procIn.key].[...]

   Data type
         strings

   Description
         Options for processing instructions. Will be defined in the respective
         third party modules.

         **Examples:**

         .....procInstrParams.tx\_staticpub\_publish.includeResources=1

   Default


.. container:: table-row

   Property
         paramSets.[key].pidsOnly

   Data type
         list of integers (pages uid)

   Description
         List of Page Ids to limit this configuration to

   Default


.. container:: table-row

   Property
         paramSets.[key].userGroups

   Data type
         list of integers (fe\_groups uid)

   Description
         User groups to set for the request.

   Default


.. container:: table-row

   Property
         paramSets.[key].cHash

   Data type
         boolean

   Description
         If set, a cHash value is calculated and added to the URLs.

   Default


.. container:: table-row

   Property
         paramSets.[key].baseUrl

   Data type
         string

   Description
         If not set, t3lib\_div::getIndpEnv('TYPO3\_SITE\_URL') is used to
         request the page.

         MUST BE SET if run from CLI (since TYPO3\_SITE\_URL does not exist in
         that context!)

   Default


.. ###### END~OF~TABLE ######

[Page TSconfig: tx\_crawler.crawlerCfg]


Example
~~~~~~~

::

   tx_crawler.crawlerCfg.paramSets.test = &L=[0-3]
   tx_crawler.crawlerCfg.paramSets.test {
           procInstrFilter = tx_indexedsearch_reindex
   }

