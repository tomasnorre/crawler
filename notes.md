# Implementation ideas

## Ideas

Create two asynchronous queues, normal and priority, so that pages that are more urgent get higher priority.
Perhaps the SEO settings, need to be remove again? Or used to determine if it's should go to normal or priority queues.

## Backend Modules

### Crawler Log

fields:

* scheduled: Will be filled when job added to message queue (Message written)
* exec_time & status: Will be filled when job is executed by message bus (Message Handled)
* set_id: Is obsolete, not really needed.
* queue_id: Links to overview, which I think can be skipped.

### Start Crawling

Will be added to message bus instead of crawler queue like today. The message bus/dispatcher will then take care of
writing the correct database entries and processing them.

### Crawler Processes

Will not really exist anymore. There can be a overview kept have many entries are still in log, but should perhaps not
take up an entire view, perhaps info on all views instead.

## Symfony Commands

### Build Queue Command

Keep "as is", but adding it to the message bus/dispatcher, like the start crawling.

### Flush Queue Command

Can be kept, but consider if it's still relevant.

### Process Queue Command

Is obsolete, as all processing will happen through the Message Bus.

## Documentation

* Show how to setup the consumer
  service: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/MessageBus/Index.html
* Show how to run the messenger through Scheduler

## Deprecations

Mark classes that will be deleted as deprecated and to be removed in v13.



