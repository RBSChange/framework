CREATE TABLE IF NOT EXISTS `f_url_rules` (
  `rule_id` int(11) NOT NULL auto_increment,
  `origine` int(11) NOT NULL DEFAULT '0',
  `modulename` varchar(50) NULL,
  `actionname` varchar(50) NULL,
  `document_id` int(11) NOT NULL,
  `website_lang` varchar(2) NOT NULL DEFAULT 'fr',
  `website_id` int(11) NOT NULL DEFAULT '0',
  `from_url` varchar(255) NOT NULL,
  `to_url` varchar(255) DEFAULT NULL,
  `redirect_type` int(11) NOT NULL DEFAULT '200',
  PRIMARY KEY  (`rule_id`),
  UNIQUE (`website_id` , `website_lang` , `from_url`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;