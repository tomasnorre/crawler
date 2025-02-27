..  include:: /Includes.rst.txt
..  highlight:: php

=====
Hooks
=====

Register the following hooks in :file:`ext_localconf.php` of your extension.

excludeDoktype Hook
===================

By adding doktype ids to following array you can exclude them from
being crawled:

..  code-block:: php
    :caption: packages/my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'][] = <dokTypeId>


pageVeto Hook
=============

..  deprecated:: 11.0.0
    Will be removed in 13.0, please migrate to the PSR-14 Event :ref:`psr14-modify-skip-page-event`!

You can also decide whether a page should not be crawled in an
individual userfunction. Register your function here:

..  code-block:: php
    :caption: packages/my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'][] = MyVendor\MyExtension\Hooks\Crawler\PageVeto::class . '->excludePage';

..  literalinclude:: _PageVeto.php
    :caption: packages/my_extension/Classes/Hooks/Crawler/PageVeto.php
