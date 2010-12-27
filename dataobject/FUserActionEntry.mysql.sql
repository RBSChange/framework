CREATE TABLE IF NOT EXISTS `f_user_action_entry` (
  `entry_id` int(11) NOT NULL auto_increment,
  `entry_date` datetime,
  `user_id` int(11) NULL,
  `document_id` int(11) NULL,
  `module_name` varchar(50) NULL,
  `action_name` varchar(50) NULL,
  `username` varchar(100) NULL,
  `info` text,
  PRIMARY KEY  (`entry_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 CHARACTER SET utf8 COLLATE utf8_bin;
