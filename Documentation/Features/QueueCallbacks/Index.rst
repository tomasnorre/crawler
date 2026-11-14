..  include:: /Includes.rst.txt

..  _queue-callbacks:

===============
Queue callbacks
===============

..  versionadded:: 14.0.0
    Callbacks implement `AOE\Crawler\Queue\QueueCallbackInterface`, see
    :ref:`upgrade-queue-callbacks`.

Besides URLs, the queue can hold callbacks: instead of crawling a URL, the
worker calls a class of your extension. This is useful for work that should
run in the background with the crawler, e.g. crawling external files. The
result is stored with the queue entry and shown in the :guilabel:`Crawler log`.

Implement the callback
======================

A callback implements `AOE\Crawler\Queue\QueueCallbackInterface`. It is a
service, so it gets its dependencies by constructor injection:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Crawler/ReindexFileCallback.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Crawler;

    use AOE\Crawler\Queue\QueueCallbackInterface;
    use MyVendor\MyExtension\Service\FileIndexer;

    final readonly class ReindexFileCallback implements QueueCallbackInterface
    {
        public function __construct(
            private FileIndexer $fileIndexer,
        ) {
        }

        public function execute(array $parameters): mixed
        {
            return $this->fileIndexer->reindex((int) $parameters['fileUid']);
        }
    }

The return value is stored JSON encoded as the result of the queue entry.

With `autoconfigure: true` in the :file:`Configuration/Services.yaml` of your
extension, which is the default, the callback is registered automatically.
Classes that are not services are created with
`GeneralUtility::makeInstance()`, without constructor arguments.

Add the callback to the queue
=============================

Add a queue entry with `AOE\Crawler\Queue\QueueEntryWriter::addCallback()`.
Inject the `QueueEntryWriter` into your own service:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/FileQueue.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Service;

    use AOE\Crawler\Queue\QueueEntryWriter;
    use MyVendor\MyExtension\Crawler\ReindexFileCallback;

    final readonly class FileQueue
    {
        public function __construct(
            private QueueEntryWriter $queueEntryWriter,
        ) {
        }

        public function reindexLater(int $fileUid, int $pageId): void
        {
            $this->queueEntryWriter->addCallback(
                ReindexFileCallback::class,
                ['fileUid' => $fileUid],
                $pageId,
            );
        }
    }

`addCallback()` takes these arguments:

`$callbackClassName`
    The class implementing `QueueCallbackInterface`.

`$parameters`
    Handed over to `execute()`. They are stored JSON encoded, so they must be
    JSON encodable.

`$pageId`
    The page the queue entry is shown with in the :guilabel:`Crawler log`,
    `0` by default.

`$scheduled`
    The Unix timestamp to execute the callback at, `0` (the default) for now.

The queue entry is dispatched to the message bus like a URL, and processed by
the worker, see :ref:`upgrade-message-bus`.
