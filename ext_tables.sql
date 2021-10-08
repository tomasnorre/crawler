#
# Table structure for table 'tx_crawler_queue'
#
CREATE TABLE tx_crawler_queue (
  qid int(11) DEFAULT '0' NOT NULL auto_increment,
  page_id int(11) DEFAULT '0' NOT NULL,
  parameters text NOT NULL,
  parameters_hash varchar(50) DEFAULT '' NOT NULL,
  configuration_hash varchar(50) DEFAULT '' NOT NULL,
  scheduled int(11) DEFAULT '0' NOT NULL,
  exec_time int(11) DEFAULT '0' NOT NULL,
  set_id int(11) DEFAULT '0' NOT NULL,
  result_data longtext NOT NULL,
  process_scheduled int(11) DEFAULT '0' NOT NULL,
  process_id varchar(50) DEFAULT '' NOT NULL,
  process_id_completed varchar(50) DEFAULT '' NOT NULL,
  configuration varchar(250) DEFAULT '' NOT NULL,

  PRIMARY KEY (qid),
  KEY page_id (page_id),
  KEY set_id (set_id),
  KEY exec_time (exec_time),
  KEY scheduled (scheduled),
  KEY process_id (process_id),
  KEY parameters_hash (parameters_hash),
  KEY configuration_hash (configuration_hash),
  KEY cleanup (exec_time,scheduled)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_crawler_process'
#
CREATE TABLE tx_crawler_process (
  process_id varchar(50) DEFAULT '' NOT NULL,
  active smallint(6) DEFAULT '0',
  ttl int(11) DEFAULT '0' NOT NULL,
  assigned_items_count int(11) DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  system_process_id int(11) DEFAULT '0' NOT NULL,

  KEY update_key (active,deleted),
  KEY process_id (process_id)
) ENGINE=InnoDB;

#
# Table structure for table 'tx_crawler_configuration'
#
CREATE TABLE tx_crawler_configuration (
  name tinytext NOT NULL,
  force_ssl tinyint(4) DEFAULT '0' NOT NULL,
  processing_instruction_filter varchar(200) DEFAULT '' NOT NULL,
  processing_instruction_parameters_ts varchar(200) DEFAULT '' NOT NULL,
  configuration text NOT NULL,
  base_url tinytext NOT NULL,
  pidsonly blob,
  begroups varchar(100) DEFAULT '0' NOT NULL,
  fegroups varchar(100) DEFAULT '0' NOT NULL,
  exclude text NOT NULL

) ENGINE=InnoDB;

#
# Table structure for table 'pages'
# This is added to reuse the information from typo3/cms-seo.
# As we don't have a dependency for typo3/cms-seo it's added here to ensure that the
# database queries isn't breaking
#
CREATE TABLE pages
(
    sitemap_priority decimal(2, 1) DEFAULT '0.5' NOT NULL
);
