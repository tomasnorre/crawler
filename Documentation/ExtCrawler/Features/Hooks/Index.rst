

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


Hooks
^^^^^

excludeDoktype Hook
~~~~~~~~~~~~~~~~~~~

By adding doktype ids to following array you can exclude them from
being crawled:

::

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['excludeDoktype'][] = <dokTypeId>


pageVeto Hook
~~~~~~~~~~~~~

You can also decide whether a page should not be crawled in an
individual userfunction. Register your function here:

::

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pageVeto'][] = 'EXT:yourext/.../class.tx_yourext_foo.php: tx_yourext_foo->bar';


