CREATE TABLE IF NOT EXISTS `f_i18n` (
	`document_id` int(11) NOT NULL DEFAULT '0',
	`document_lang` char(2) NOT NULL DEFAULT 'fr',
	`synchro_status` ENUM('MODIFIED', 'VALID', 'SYNCHRONIZED') NOT NULL DEFAULT 'MODIFIED',
	`synchro_from` char(2) NULL DEFAULT NULL,
PRIMARY KEY  (`document_id`, `document_lang`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;