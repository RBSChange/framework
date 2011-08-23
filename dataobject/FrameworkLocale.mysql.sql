CREATE TABLE IF NOT EXISTS `f_locale` (
 `lang` char(5) NOT NULL,
 `id` varchar(100) NOT NULL,
 `key_path` varchar(100) NOT NULL,
 `content` text,
 `useredited` tinyint(1) NOT NULL default '0',
 `format` ENUM('TEXT', 'HTML') NOT NULL DEFAULT 'TEXT',
 PRIMARY KEY (`key_path`,`lang`,`id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;