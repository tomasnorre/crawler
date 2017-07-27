#
# Table structure for table 'tx_crawler_domain_model_crawlerqueueitem'
#
CREATE TABLE tx_crawler_domain_model_crawlerqueueitem (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  crdate int(11) DEFAULT '0' NOT NULL,
  cruser_id int(11) DEFAULT '0' NOT NULL,
  deleted tinyint(4) DEFAULT '0' NOT NULL,
  hidden tinyint(4) DEFAULT '0' NOT NULL,

  page_uid int(11) DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);