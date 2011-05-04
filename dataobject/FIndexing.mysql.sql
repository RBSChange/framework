CREATE TABLE IF NOT EXISTS `f_indexing` (
 `document_id` int(11) NOT NULL,
 `indexing_mode` tinyint(1) NOT NULL DEFAULT '1',
 `indexing_status` enum('TO_INDEX','TO_DELETE','INDEXED') COLLATE utf8_bin NOT NULL DEFAULT 'TO_INDEX',
 `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`document_id`,`indexing_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin