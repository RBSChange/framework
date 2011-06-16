CREATE TABLE IF NOT EXISTS `f_url_rules` (
  `rule_id` int(11) NOT NULL auto_increment,
  `document_id` int(11) NOT NULL,
  `document_lang` varchar(2) NOT NULL DEFAULT 'fr',
  `website_id` int(11) NOT NULL DEFAULT '0',
  `from_url` varchar(255) NOT NULL,
  `to_url` varchar(255) DEFAULT NULL,
  `redirect_type` int(11) NOT NULL DEFAULT '200',
  PRIMARY KEY  (`rule_id`),
  UNIQUE url (`website_id`, `from_url`, `document_lang`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;