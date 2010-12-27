CREATE TABLE IF NOT EXISTS `f_data_cache` (
`cache_key` VARCHAR(255) NOT NULL ,
`text_value` MEDIUMTEXT NOT NULL ,
`creation_time` INT(11) NOT NULL ,
`ttl` INT(11) ,
`is_valid` tinyint(1) NOT NULL DEFAULT '0' ,
PRIMARY KEY ( `cache_key` )
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;