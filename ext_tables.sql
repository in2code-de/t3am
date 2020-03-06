CREATE TABLE tx_t3am_encryption_key (
  uid int(11) NOT NULL auto_increment,
  private_key text,
  public_key text,

  PRIMARY KEY (uid)
);

CREATE TABLE tx_t3am_client (
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
