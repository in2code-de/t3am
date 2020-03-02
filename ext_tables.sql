CREATE TABLE tx_t3amserver_keys (
  uid int(11) NOT NULL auto_increment,
  key_value text,

  PRIMARY KEY (uid)
);

CREATE TABLE tx_t3amserver_client (
  uid int(11) NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,

  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,
  disabled tinyint(4) unsigned DEFAULT '0' NOT NULL,

  identifier varchar(255) DEFAULT '' NOT NULL,
  instance_notice text,
  token varchar(255) DEFAULT '' NOT NULL,

  PRIMARY KEY (uid),
  KEY token (token),
  KEY parent (pid)
);
