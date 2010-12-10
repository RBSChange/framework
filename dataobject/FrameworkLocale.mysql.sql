CREATE TABLE IF NOT EXISTS `f_locale` (
 `lang` char(5) COLLATE utf8_bin NOT NULL,
 `id` varchar(100) COLLATE utf8_bin NOT NULL,
 `key_path` varchar(100) COLLATE utf8_bin NOT NULL,
 `content` text COLLATE utf8_bin,
 `useredited` tinyint(1) NOT NULL default '0',
 `format` ENUM('TEXT', 'HTML') NOT NULL DEFAULT 'TEXT',
 PRIMARY KEY (`key_path`,`lang`,`id`)
) TYPE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;