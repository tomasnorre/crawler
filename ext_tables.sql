#
# Table structure for table 'tx_crawler_queue'
#
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
    configuration        varchar(250) DEFAULT '' NOT NULL,

    PRIMARY KEY (qid),
    KEY                  page_id (page_id),
    KEY                  set_id (set_id),
    KEY                  exec_time (exec_time),
    KEY                  scheduled (scheduled),
    KEY                  parameters_hash (parameters_hash),
    KEY                  configuration_hash (configuration_hash),
    KEY                  cleanup (exec_time,scheduled)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_crawler_configuration'
#
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
