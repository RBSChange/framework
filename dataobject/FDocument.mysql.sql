CREATE TABLE IF NOT EXISTS `f_document` (
  `document_id` int(11) NOT NULL auto_increment,
  `document_model` varchar(50) NOT NULL default '',
  `treeid` int(11) NULL,
  `lang_vo` varchar(2) NOT NULL default '',
  PRIMARY KEY  (`document_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10000 CHARACTER SET utf8 COLLATE utf8_bin;
