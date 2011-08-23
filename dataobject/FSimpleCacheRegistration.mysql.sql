CREATE TABLE IF NOT EXISTS `f_simplecache_registration` (
  `pattern` varchar(255) NOT NULL,
  `cache_id` varchar(255) NOT NULL,
   KEY `pattern` (`pattern`),
   KEY `cache_id` (`cache_id`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;