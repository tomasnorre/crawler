

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


FAQ
---

The crawler won't process all entrys at command-line-way. This might
happened because the php run into an time out, to avoid this you can
call the crawler like:

::

   php -d max_execution_time=512 typo3/cli_dispatch.phpsh


