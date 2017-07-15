#
# Table structure for table 'tx_crawler_queue'
#
CREATE TABLE tx_crawler_domain_model_crawlerqueueitem (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  pageUid int(11) DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);