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
  configuration varchar(50) DEFAULT '' NOT NULL,

  PRIMARY KEY (qid),
  KEY page_id (page_id),
  KEY set_id (set_id),
  KEY exec_time (exec_time),
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
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  crdate int(11) DEFAULT '0' NOT NULL,
  cruser_id int(11) DEFAULT '0' NOT NULL,
  deleted tinyint(4) DEFAULT '0' NOT NULL,
  hidden tinyint(4) DEFAULT '0' NOT NULL,
  name tinytext NOT NULL,
  force_ssl tinyint(4) DEFAULT '0' NOT NULL,
  processing_instruction_filter tinytext NOT NULL,
  processing_instruction_parameters_ts text NOT NULL,
  configuration text NOT NULL,
  base_url tinytext NOT NULL,
  sys_domain_base_url tinytext NOT NULL,
  pidsonly blob NOT NULL,
  begroups varchar(100) DEFAULT '0' NOT NULL,
  fegroups varchar(100) DEFAULT '0' NOT NULL,
  realurl tinyint(4) DEFAULT '0' NOT NULL,
  chash tinyint(4) DEFAULT '0' NOT NULL,
  exclude text NOT NULL,
  root_template_pid int(11) DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
) ENGINE=InnoDB;
