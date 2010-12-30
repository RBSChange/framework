CREATE TABLE IF NOT EXISTS `f_permission_compiled` (
  `accessor_id` int(11) NOT NULL default '0',
  `permission` varchar(100) character set latin1 collate latin1_general_ci NOT NULL,
  `node_id` int(11) NOT NULL default '0',
  PRIMARY KEY (`node_id`, `accessor_id`,`permission`)
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;
