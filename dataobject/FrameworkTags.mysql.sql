CREATE TABLE IF NOT EXISTS `f_tags` (
  `id` int(11) default NULL,
  `tag` varchar(255) default NULL,
  UNIQUE KEY `NewIndex` (`id`,`tag`),
  KEY `NewIndex2` (`tag`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;
