..  include:: /Includes.rst.txt

..  _extension-manager-configuration:

===============================
Extension Manager Configuration
===============================

A lot of options were added to the extension manager configuration,
that allow settings to improve and enable new crawler features:

..  figure:: /Images/backend_configuration_settings.png
    :alt: Backend configuration: Settings

    Backend configuration: Settings

..  figure:: /Images/backend_configuration_queue.png
    :alt: Backend configuration: Queue

    Backend configuration: Queue

..  _extension-manager-configuration-direct-requests:

Direct requests
===============

With `makeDirectRequests` enabled, the crawler renders the pages within the
worker process (`messenger:consume`), by handing a request to the TYPO3
frontend application, like TYPO3 does for its error pages. The pages are not
fetched by HTTP, so the web server isn't involved and no PHP binary has to be
configured.

The worker restores its state after each page. If your extensions keep state of
their own between requests, limit the run time of the worker, fx. with
`messenger:consume crawler_high crawler crawler_low --limit=500`.

`frontendBasePath`
    Base path of the frontend, fx. `/cms/` if the frontend is called by
    `https://example.com/cms/index.php`. Defaults to `/`.
