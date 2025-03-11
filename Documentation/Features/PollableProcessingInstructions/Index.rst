..  include:: /Includes.rst.txt

..  _pollable-processing:

================================
Pollable processing instructions
================================

Some processing instructions are never executed on the "client side"
(the TYPO3 frontend that is called by the crawler). This happens for
example if a try to staticpub a page containing non-cacheable
elements. That bad thing about this is, that staticpub doesn't have
any chance to tell that something went wrong and why. That's why we
introduced the "pollable processing instructions" feature. You can
define in the :file:`ext_localconf.php` file of your extension that this
extension should be "pollable" bye adding following line:

..  code-block:: php
    :caption: packages/my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crawler']['pollSuccess'][] = 'tx_staticpub';

In this case the crawler expects the extension to tell if everything
was ok actively, assuming that something went wrong (and displaying
this in the log) is no "success message" was found.

In your extension than simple write your "ok" status by calling this:

..  code-block:: php
    :caption: packages/my_extension/ext_localconf.php

    $GLOBALS['TSFE']->applicationData['tx_crawler']['success']['tx_staticpub'] = true;

