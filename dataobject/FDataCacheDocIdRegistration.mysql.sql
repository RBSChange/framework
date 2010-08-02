CREATE TABLE IF NOT EXISTS `f_data_cache_doc_id_registration` (
`document_id` INT(11) NOT NULL ,
`key_parameters` TEXT NOT NULL ,
PRIMARY KEY ( `document_id` )
) TYPE=MyISAM CHARACTER SET utf8 COLLATE utf8_bin;