# Feature: Message Queue in TYPO3 Crawler

I've been working regularly to improve the TYPO3 Crawler since I started contributing more than 10 years ago.

After the Message Queue system was introduced to TYPO3 I think it's time to rethink the TYPO3 Crawler a little. There is to much logic in the crawler that could be replaced with core functionality. Which is the aim for this feature.

## Disclaimer

This feature will be a breaking change with 99% certainty. I don't want to maintain both paths for a period of time to keep backwards compatibility.

## Idea

The crawler queue it self will not be handled by the crawler it self anymore, only the result of a page visit, for later revisit and inspection.

### SQL Tables

Today there is 3 sql tables in the TYPO3 Crawler.

```sql
CREATE TABLE tx_crawler_queue
(
    qid                  int(11) DEFAULT '0' NOT NULL auto_increment,
    page_id              int(11) DEFAULT '0' NOT NULL,
    parameters           text                    NOT NULL,
    parameters_hash      varchar(50)  DEFAULT '' NOT NULL,
    configuration_hash   varchar(50)  DEFAULT '' NOT NULL,
    scheduled            int(11) DEFAULT '0' NOT NULL,
    exec_time            int(11) DEFAULT '0' NOT NULL,
    set_id               int(11) DEFAULT '0' NOT NULL,
    result_data          longtext                NOT NULL,
    process_scheduled    int(11) DEFAULT '0' NOT NULL,
    process_id           varchar(50)  DEFAULT '' NOT NULL,
    process_id_completed varchar(50)  DEFAULT '' NOT NULL,
    configuration        varchar(250) DEFAULT '' NOT NULL,

    PRIMARY KEY (qid),
    KEY                  page_id (page_id),
    KEY                  set_id (set_id),
    KEY                  exec_time (exec_time),
    KEY                  scheduled (scheduled),
    KEY                  process_id (process_id),
    KEY                  parameters_hash (parameters_hash),
    KEY                  configuration_hash (configuration_hash),
    KEY                  cleanup (exec_time,scheduled)
) ENGINE=InnoDB;
```

As one can see the `tx_crawler_queue` holds quite some data, which configuration is has been used from crawling, if it's scheduled or not, the execution time it too, the set_id of which it was part of, result data, and much more. Much of this information is to be honest not relevant to many people if any.

This table can be more or less completely removed.

```sql
CREATE TABLE tx_crawler_process
(
    process_id           varchar(50) DEFAULT '' NOT NULL,
    active               smallint(6) DEFAULT '0',
    ttl                  int(11) DEFAULT '0' NOT NULL,
    assigned_items_count int(11) DEFAULT '0' NOT NULL,
    deleted              tinyint(4) unsigned DEFAULT '0' NOT NULL,
    system_process_id    int(11) DEFAULT '0' NOT NULL,

    KEY                  update_key (active,deleted),
    KEY                  process_id (process_id)
) ENGINE=InnoDB;
```

The `tx_crawler_process` table can be completely remove as the processes with be handle by the message bus. This logic related to this will not be needed in the TYPO3 Crawler anymore.

```sql
CREATE TABLE tx_crawler_configuration
(
    name                                 tinytext                 NOT NULL,
    force_ssl                            tinyint(4) DEFAULT '0' NOT NULL,
    processing_instruction_filter        varchar(200) DEFAULT ''  NOT NULL,
    processing_instruction_parameters_ts varchar(200) DEFAULT ''  NOT NULL,
    configuration                        text                     NOT NULL,
    base_url                             tinytext                 NOT NULL,
    pidsonly                             blob,
    begroups                             varchar(100) DEFAULT '0' NOT NULL,
    fegroups                             varchar(100) DEFAULT '0' NOT NULL,
    exclude                              text                     NOT NULL

) ENGINE=InnoDB;
```

The `tx_crawler_configuration` might exists in some form or the other. The is a suggestion to let the TYPO3 Crawler simple run over the `sitemap.xml` file, which will remove the real need of a configuration. I still think some kind of configuration is needed to have some possibility to exclude some pages in crawler, when e.g. the TYPO3 Crawler is used as a Cache warmup.

#### Classes

There is quite a number of classes that will be trimmed down and even obsolete after doing this change. So I'll only outline the new Classes that I would expect to be present after the refactor.

```php
class CrawlPageMessage
{
    public function __construct(
        private readonly int $pageId,
    ){}
}
```

```php
class CrawlPageHandler
{
    public function __invoke(CrawlPageMessage $message)
    {
        // Crawl Page and pass status to the dispatched message
        // Dispatch PageStatusMessage
    }
}
```

```php
class PageStatusMessage
{
    public function __construct(
        private readonly int $pageId,
        private readonly PageStatus $pageStatus
    ){}
}
```

```php
class PageStatusHandler
{
    public function __invoke(PageStatusMessage $message)
    {
        // Update "log" for given page with status
    }
}
```

Whit these changes most of the `Domain/Model` and `Domain/Repository` present to day will be obsolete. Most of the classes Related to that too.

## Links

-   https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/MessageBus/Index.html
-   https://usetypo3.com/messages-in-typo3/
-   https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.2/Feature-97700-AdoptSymfonyMessengerAsAMessageBusAndQueue.html
