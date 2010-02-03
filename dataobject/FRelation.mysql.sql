CREATE TABLE IF NOT EXISTS `f_relation` (
  `relation_id1` int(11) NOT NULL default '0',
  `relation_id2` int(11) NOT NULL default '0',
  `relation_order` int(11) NOT NULL default '0',
  `relation_name` varchar(50) NOT NULL default '',
  `document_model_id1` varchar(50) NOT NULL default '',
  `document_model_id2` varchar(50) NOT NULL default '',
  `relation_id` int(11) NOT NULL default '0',
  PRIMARY KEY ( `relation_id1` , `relation_id` , `relation_order`),
  INDEX `relation_id2` ( `relation_id2`, `relation_id` )
) TYPE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;