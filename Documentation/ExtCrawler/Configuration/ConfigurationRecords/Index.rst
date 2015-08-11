

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

Formerly configuration was done by using page ts (see below). This is
still possible (fully backwards compatible) but not recommended.
Instead of writing pageTS simply create a configuration record (table:
tx\_crawler\_configuration) and put it on the topmost page of the
pagetree you want to affect with this configuration.

The fields in these records are related to the page ts keys described
below. The “name” fields corresponds to the “key” in the pageTS setup.

.. image:: /Images/backend_configurationrecord.png