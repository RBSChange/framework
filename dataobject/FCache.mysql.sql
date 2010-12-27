CREATE TABLE IF NOT EXISTS `f_cache` (
`cache_key` int(11) NOT NULL ,
`text_value` MEDIUMTEXT NOT NULL ,
PRIMARY KEY ( `cache_key` )
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;