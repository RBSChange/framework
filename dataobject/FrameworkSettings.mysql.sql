CREATE TABLE IF NOT EXISTS f_settings (
  name varchar(50) NOT NULL default '',
  package varchar(255) NOT NULL default '',
  userid bigint(20) unsigned NOT NULL default '0',
  value varchar(50) NOT NULL default '',
  UNIQUE KEY NewIndex (name,package,userid)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;

